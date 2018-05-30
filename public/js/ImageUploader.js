/**
 * ImageUploader.js - a client-side image resize and upload javascript module
 * 
 * @author Ross Turner (https://github.com/zsinj)
 */
var ImageUploader = function(config) {
    if (!config || (!config.inputElement) || (!config.inputElement.getAttribute) || config.inputElement.getAttribute('type') !== 'file') {
        throw new Error('Config object passed to ImageUploader constructor must include "inputElement" set to be an element of type="file"');
    }
    this.setConfig(config);

    var This = this;
    this.config.inputElement.addEventListener('change', function(event) {
        var fileArray = [];
        var cursor = 0;
        for (; cursor < This.config.inputElement.files.length; ++cursor) {
            fileArray.push(This.config.inputElement.files[cursor]);
        }
        This.progressObject = {
            total : parseInt(fileArray.length, 10),
            done : 0,
            currentItemTotal : 0,
            currentItemDone : 0
        };
        if (typeof This.config.onProgress === 'function') {
            This.config.onProgress(This.progressObject);
        }
        This.handleFileList(fileArray, This.progressObject);
    }, false);

    if (This.config.debug) {
        console.log('Initialised ImageUploader for ' + This.config.inputElement);
    }

};

ImageUploader.prototype.handleFileList = function(fileArray) {
    var This = this;
    if (fileArray.length > 1) {
        var file = fileArray.shift();
        this.handleFileSelection(file, function() {
            This.handleFileList(fileArray);
        });
    } else if (fileArray.length === 1) {
        this.handleFileSelection(fileArray[0], function() {
            if (typeof This.config.onComplete === 'function') {
                This.config.onComplete(This.progressObject);
            }
        });
    }
};

ImageUploader.prototype.hideImage = function(passwordCtx, coverCtx) {
    var passwordImageData = passwordCtx.getImageData(0, 0, passwordCtx.canvas.width, passwordCtx.canvas.height);
    var coverImageData = coverCtx.getImageData(0, 0, coverCtx.canvas.width, coverCtx.canvas.height);
    for (var i = 0; i < coverImageData.data.length; i += 4) {
        var byte = passwordImageData.data[i];
        var r = byte >> 5 & 0x7;
        var g = byte >> 3 & 0x3;
        var b = byte & 0x7;
        coverImageData.data[i] = (coverImageData.data[i] & ~0x7) | r;
        coverImageData.data[i + 1] = (coverImageData.data[i + 1] & ~0x3) | g;
        coverImageData.data[i + 2] = (coverImageData.data[i + 2] & ~0x7) | b;
        coverImageData.data[i + 3] = 255;
    }
    coverCtx.putImageData(coverImageData, 0, 0);
}

ImageUploader.prototype.handleFileSelection = function(file, completionCallback) {
    var img = document.createElement('img');
    this.currentFile = file;
    var reader = new FileReader();
    var This = this;
    reader.onload = function(e) {
        img.src = e.target.result;

        img.onload = function() {
			//Rotate image first if required
			if (false && This.config.autoRotate) {
				if (This.config.debug)
					console.log('ImageUploader: detecting image orientation...');
				if ( (typeof EXIF.getData === "function") && (typeof EXIF.getTag === "function") ) {
					EXIF.getData(img, function() {
						var orientation = EXIF.getTag(this, "Orientation");
						if (This.config.debug) {
							console.log('ImageUploader: image orientation from EXIF tag = ' + orientation);
						}
						This.scaleImage(img, completionCallback, orientation);
					});
				}
				else {
					console.error("ImageUploader: can't read EXIF data, the Exif.js library not found");
					This.scaleImage(img, completionCallback);
                }
            }
            var canvas = document.createElement('canvas');
            canvas.width = img.width;
            canvas.height = img.height;
            var ctx = canvas.getContext('2d');
            ctx.save();
            ctx.drawImage(img, 0, 0);
            ctx.restore();
            
            var row = document.createElement("div");
            row.className = "row";
            This.config.workspace.appendChild(row);

            var col1 = document.createElement("div");
            col1.className = "col-md-3 col-xs-3";
            var orgImage = document.createElement('img');
            orgImage.src = canvas.toDataURL('image/png');
            orgImage.className = "img-fluid img-thumbnail";
            col1.appendChild(orgImage);
            var cropDesc = document.createElement("p");
            cropDesc.className = "text-center";
            cropDesc.innerText = img.width + " x " + img.height;
            col1.appendChild(cropDesc);
            row.appendChild(col1);

            if (This.config.login) {
                // Crop
                var cropX = Math.floor(Math.random() * 100);
                var cropY = Math.floor(Math.random() * 100);
                This.cropImage(ctx, cropX, cropY, img.width, img.height);

                var col2 = document.createElement("div");
                col2.className = "col-md-3 col-xs-3";
                var croppedImage = document.createElement('img');
                croppedImage.src = canvas.toDataURL('image/png');
                croppedImage.className = "img-fluid img-thumbnail";
                col2.appendChild(croppedImage);
                var cropDesc = document.createElement("p");
                cropDesc.className = "text-center";
                cropDesc.innerText = "(x, y) = (" + cropX + ", " + cropY + ")";
                col2.appendChild(cropDesc);
                row.appendChild(col2);

                // Encrypt
                This.encryptImage(ctx, This.config.key, img.width, img.height);
                var col3 = document.createElement("div");
                col3.className = "col-md-3 col-xs-3";
                var encImage = document.createElement('img');
                encImage.src = canvas.toDataURL('image/png');
                encImage.className = "img-fluid img-thumbnail";
                col3.appendChild(encImage);
                var encDesc = document.createElement("p");
                encDesc.className = "text-center";
                encDesc.innerText = "";
                col3.appendChild(encDesc);
                row.appendChild(col3);
                
                // Watermark
                var coverImage = document.createElement('img');
                coverImage.onload = function() {
                    var coverCanvas = document.createElement('canvas');
                    coverCanvas.width = img.width;
                    coverCanvas.height = img.height;
                    var coverCtx = coverCanvas.getContext('2d');
                    coverCtx.save();
                    coverCtx.drawImage(coverImage, 0, 0);
                    coverCtx.restore();
                    This.hideImage(ctx, coverCtx);

                    var col4 = document.createElement("div");
                    col4.className = "col-md-3 col-xs-3";
                    var hiddenImage = document.createElement('img');
                    hiddenImage.src = coverCanvas.toDataURL('image/png');
                    hiddenImage.className = "img-fluid img-thumbnail";
                    col4.appendChild(hiddenImage);
                    var hidDesc = document.createElement("p");
                    hidDesc.className = "text-center";
                    hidDesc.innerText = img.width + " x " + img.height;
                    col4.appendChild(hidDesc);
                    row.appendChild(col4);

                    // Upload
                    var imageData = coverCanvas.toDataURL('image/png');
                    This.performUpload(imageData, completionCallback);
                }
                coverImage.src = '/login/cover?rand=' + new Date().getTime();
            } else {
                // Upload
                var imageData = canvas.toDataURL('image/png');
                This.performUpload(imageData, completionCallback);
            }
        }
    };
    reader.readAsDataURL(file);
};

ImageUploader.prototype.cropImage = function(ctx, x, y, width, height) {
    ctx.save();
    ctx.fillStyle = 0;
    ctx.fillRect(0, 0, width, y);
    ctx.fillRect(0, 0, x, height);
    ctx.restore();
    return ctx;
}

function grayCanvasToString(ctx) {
    var imgData = ctx.getImageData(0, 0, ctx.canvas.width, ctx.canvas.height);
    var result = "";
    for (var i = 0; i < imgData.data.length; i += 4)
		result += String.fromCharCode(imgData.data[i]);
    return result;
}

function stringToGrayCanvas(ctx, imgStr) {
    var imgData = ctx.getImageData(0, 0, ctx.canvas.width, ctx.canvas.height);
    for (var i = 0; i < imgStr.length; ++i)
        imgData.data[i * 4] = imgData.data[i * 4 + 1] = imgData.data[i * 4 + 2] = 
        imgStr[i].charCodeAt();
    ctx.putImageData(imgData, 0, 0);
    return ctx;
}

ImageUploader.prototype.encryptImage = function(ctx, key, width, height) {
    ctx.save();
    //img = $('img')[0];
    //canvas = document.createElement('canvas');
    //canvas.width = img.width;
    //canvas.height = img.height;
    //ctx = canvas.getContext('2d');
    //ctx.drawImage(img, 0, 0);
    var cipher = CryptoJS.enc.Latin1.parse(grayCanvasToString(ctx));
    var key = CryptoJS.enc.Utf8.parse(key);
    var iv = key;
    var encrypted = CryptoJS.AES.encrypt(cipher, key, {
        keySize: 128 / 8,
        iv: iv,
        mode: CryptoJS.mode.CBC,
        padding: CryptoJS.pad.NoPadding
    });
    var bytes = encrypted.ciphertext.toString(CryptoJS.enc.Latin1);
    stringToGrayCanvas(ctx, bytes);
    ctx.restore();
    return ctx;
}

ImageUploader.prototype.drawImage = function(context, img, x, y, width, height, deg, flip, flop, center) {
	context.save();

	if(typeof width === "undefined") width = img.width;
	if(typeof height === "undefined") height = img.height;
	if(typeof center === "undefined") center = false;

	// Set rotation point to center of image, instead of top/left
	if(center) {
		x -= width/2;
		y -= height/2;
	}

	// Set the origin to the center of the image
	context.translate(x + width/2, y + height/2);

	// Rotate the canvas around the origin
	var rad = 2 * Math.PI - deg * Math.PI / 180;    
	context.rotate(rad);

	// Flip/flop the canvas
	if(flip) flipScale = -1; else flipScale = 1;
	if(flop) flopScale = -1; else flopScale = 1;
	context.scale(flipScale, flopScale);

	// Draw the image    
	context.drawImage(img, -width/2, -height/2, width, height);

	context.restore();
}

ImageUploader.prototype.scaleImage = function(img, completionCallback, orientation) {
    var canvas = document.createElement('canvas');
	canvas.width = img.width;
	canvas.height = img.height;
	var ctx = canvas.getContext('2d');	
	ctx.save();
	
	//Good explanation of EXIF orientation is here http://www.daveperrett.com/articles/2012/07/28/exif-orientation-handling-is-a-ghetto/
	var width  = canvas.width;
	var styleWidth  = canvas.style.width;
    var height = canvas.height;
	var styleHeight = canvas.style.height;
	if (typeof orientation === 'undefined')
        orientation = 1;
    if (orientation) {
		if (orientation > 4) {
			canvas.width  = height;
			canvas.style.width  = styleHeight;
			canvas.height = width;
			canvas.style.height = styleWidth;
		}
		switch (orientation) {
			case 2: 
				ctx.translate(width, 0);
				ctx.scale(-1,1);
				break;
			case 3: 
				ctx.translate(width,height);
				ctx.rotate(Math.PI);
				break;
			case 4:
				ctx.translate(0,height);
				ctx.scale(1,-1);
				break;
			case 5:
				ctx.rotate(0.5 * Math.PI);
				ctx.scale(1,-1);
				break;
			case 6:
				ctx.rotate(0.5 * Math.PI);
				ctx.translate(0,-height);
				break;
			case 7: 
				ctx.rotate(0.5 * Math.PI);
				ctx.translate(width,-height);
				ctx.scale(-1,1);
				break;
			case 8:
				ctx.rotate(-0.5 * Math.PI);
				ctx.translate(-width,0);
				break;
		}
    }
	ctx.drawImage(img, 0, 0);
	ctx.restore();

	//Let's find the max available width for scaled image
	var ratio = canvas.width/canvas.height;
	var mWidth = Math.min(this.config.maxWidth, ratio*this.config.maxHeight);
	if ( (this.config.maxSize>0) && (this.config.maxSize<canvas.width*canvas.height/1000) )
		mWidth = Math.min(mWidth, Math.floor(Math.sqrt(this.config.maxSize*ratio)));
	if ( !!this.config.scaleRatio )
		mWidth = Math.min(mWidth, Math.floor(this.config.scaleRatio*canvas.width));
	
	if (this.config.debug){
		console.log('ImageUploader: original image size = ' + canvas.width + ' px (width) X ' + canvas.height + ' px (height)');
		console.log('ImageUploader: scaled image size = ' + mWidth + ' px (width) X ' + Math.floor(mWidth/ratio) + ' px (height)');
	}
	if (mWidth<=0){
		mWidth = 1;
		console.warning('ImageUploader: image size is too small');
	}
	
    while (canvas.width >= (2 * mWidth)) {
        canvas = this.getHalfScaleCanvas(canvas);
    }

    if (canvas.width > mWidth) {
        canvas = this.scaleCanvasWithAlgorithm(canvas, mWidth);
    }

    var imageData = canvas.toDataURL('image/jpeg', this.config.quality);
	if (typeof this.config.onScale === 'function')
		this.config.onScale(imageData);
    this.performUpload(imageData, completionCallback);
};

function dataURItoBlob(dataURI) {
    // convert base64/URLEncoded data component to raw binary data held in a string
    var byteString;
    if (dataURI.split(',')[0].indexOf('base64') >= 0)
        byteString = atob(dataURI.split(',')[1]);
    else
        byteString = unescape(dataURI.split(',')[1]);

    // separate out the mime component
    var mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];

    // write the bytes of the string to a typed array
    var ia = new Uint8Array(byteString.length);
    for (var i = 0; i < byteString.length; i++) {
        ia[i] = byteString.charCodeAt(i);
    }

    return new Blob([ia], {type:mimeString});
}

ImageUploader.prototype.performUpload = function(imageData, completionCallback) {
    var xhr = new XMLHttpRequest();
    var This = this;
    var uploadInProgress = true;
    var headers = this.config.requestHeaders;
    xhr.onload = function(e) {
        uploadInProgress = false;
        This.uploadComplete(e, completionCallback);
    };
    xhr.upload.addEventListener("progress", function(e) {
        This.progressUpdate(e.loaded, e.total);
    }, false);
    xhr.open('POST', this.config.uploadUrl, true);
    
    if(typeof headers === 'object' && headers !== null) {
        Object.keys(headers).forEach(function(key,index) {
            if(typeof headers[key] !== 'string') {
                var headersArray = headers[key];
                for(var i = 0, j = headersArray.length; i < j; i++) {
                    xhr.setRequestHeader(key, headersArray[i]);
                }   
            } else {
                xhr.setRequestHeader(key, headers[key]);                
            }
        });
    }
    
    var data = new FormData();
    data.append('data', dataURItoBlob(imageData), "file");
    data.append('_token', this.config.csrfToken);
    xhr.send(data);

    if (this.config.timeout) {
        setTimeout(function() {
            if (uploadInProgress) {
                xhr.abort();
                This.uploadComplete({
                    target: {
                        status: 'Timed out' 
                    }
                }, completionCallback);
            }
        }, this.config.timeout);
    }
};

ImageUploader.prototype.uploadComplete = function(event, completionCallback) {
    this.progressObject.done++;
    this.progressUpdate(0, 0);
    completionCallback();
    if (typeof this.config.onFileComplete === 'function') {
        this.config.onFileComplete(event, this.currentFile);
    }
};

ImageUploader.prototype.progressUpdate = function(itemDone, itemTotal) {
    console.log('Uploaded ' + itemDone + ' of ' + itemTotal);
    this.progressObject.currentItemDone = itemDone;
    this.progressObject.currentItemTotal = itemTotal;
    if (this.config.onProgress) {
        this.config.onProgress(this.progressObject);
    }
};

ImageUploader.prototype.scaleCanvasWithAlgorithm = function(canvas, maxWidth) {
    var scaledCanvas = document.createElement('canvas');

    var scale = maxWidth / canvas.width;

    scaledCanvas.width = canvas.width * scale;
    scaledCanvas.height = canvas.height * scale;

    var srcImgData = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height);
    var destImgData = scaledCanvas.getContext('2d').createImageData(scaledCanvas.width, scaledCanvas.height);

    this.applyBilinearInterpolation(srcImgData, destImgData, scale);

    scaledCanvas.getContext('2d').putImageData(destImgData, 0, 0);

    return scaledCanvas;
};

ImageUploader.prototype.getHalfScaleCanvas = function(canvas) {
    var halfCanvas = document.createElement('canvas');
    halfCanvas.width = canvas.width / 2;
    halfCanvas.height = canvas.height / 2;

    halfCanvas.getContext('2d').drawImage(canvas, 0, 0, halfCanvas.width, halfCanvas.height);

    return halfCanvas;
};

ImageUploader.prototype.applyBilinearInterpolation = function(srcCanvasData, destCanvasData, scale) {
    function inner(f00, f10, f01, f11, x, y) {
        var un_x = 1.0 - x;
        var un_y = 1.0 - y;
        return (f00 * un_x * un_y + f10 * x * un_y + f01 * un_x * y + f11 * x * y);
    }
    var i, j;
    var iyv, iy0, iy1, ixv, ix0, ix1;
    var idxD, idxS00, idxS10, idxS01, idxS11;
    var dx, dy;
    var r, g, b, a;
    for (i = 0; i < destCanvasData.height; ++i) {
        iyv = i / scale;
        iy0 = Math.floor(iyv);
        // Math.ceil can go over bounds
        iy1 = (Math.ceil(iyv) > (srcCanvasData.height - 1) ? (srcCanvasData.height - 1) : Math.ceil(iyv));
        for (j = 0; j < destCanvasData.width; ++j) {
            ixv = j / scale;
            ix0 = Math.floor(ixv);
            // Math.ceil can go over bounds
            ix1 = (Math.ceil(ixv) > (srcCanvasData.width - 1) ? (srcCanvasData.width - 1) : Math.ceil(ixv));
            idxD = (j + destCanvasData.width * i) * 4;
            // matrix to vector indices
            idxS00 = (ix0 + srcCanvasData.width * iy0) * 4;
            idxS10 = (ix1 + srcCanvasData.width * iy0) * 4;
            idxS01 = (ix0 + srcCanvasData.width * iy1) * 4;
            idxS11 = (ix1 + srcCanvasData.width * iy1) * 4;
            // overall coordinates to unit square
            dx = ixv - ix0;
            dy = iyv - iy0;
            // I let the r, g, b, a on purpose for debugging
            r = inner(srcCanvasData.data[idxS00], srcCanvasData.data[idxS10], srcCanvasData.data[idxS01], srcCanvasData.data[idxS11], dx, dy);
            destCanvasData.data[idxD] = r;

            g = inner(srcCanvasData.data[idxS00 + 1], srcCanvasData.data[idxS10 + 1], srcCanvasData.data[idxS01 + 1], srcCanvasData.data[idxS11 + 1], dx, dy);
            destCanvasData.data[idxD + 1] = g;

            b = inner(srcCanvasData.data[idxS00 + 2], srcCanvasData.data[idxS10 + 2], srcCanvasData.data[idxS01 + 2], srcCanvasData.data[idxS11 + 2], dx, dy);
            destCanvasData.data[idxD + 2] = b;

            a = inner(srcCanvasData.data[idxS00 + 3], srcCanvasData.data[idxS10 + 3], srcCanvasData.data[idxS01 + 3], srcCanvasData.data[idxS11 + 3], dx, dy);
            destCanvasData.data[idxD + 3] = a;
        }
    }
};

ImageUploader.prototype.setConfig = function(customConfig) {
    this.config = customConfig;
    this.config.debug = this.config.debug || false;
    this.config.quality = 1.00;
    if (0.00 < customConfig.quality && customConfig.quality <= 1.00) {
        this.config.quality = customConfig.quality;
    }
    if ( (!this.config.maxWidth) || (this.config.maxWidth<0) ){
        this.config.maxWidth = 1024;
    }
	if ( (!this.config.maxHeight) || (this.config.maxHeight<0) ) {
        this.config.maxHeight = 1024;
    }
	if ( (!this.config.maxSize) || (this.config.maxSize<0) ) {
		this.config.maxSize = null;
	}
	if ( (!this.config.scaleRatio) || (this.config.scaleRatio <= 0) || (this.config.scaleRatio >= 1) ) {
		this.config.scaleRatio = null;
	}
	this.config.autoRotate = true;
	if (typeof customConfig.autoRotate === 'boolean')
		this.config.autoRotate = customConfig.autoRotate;

    // Create container if none set
    if (!this.config.workspace) {
        this.config.workspace = document.createElement('div');
        document.body.appendChild(this.config.workspace);
    }
};
