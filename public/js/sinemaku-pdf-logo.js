(function (window) {
    'use strict';

    var logoCache = {};

    function cacheKey(image) {
        return image && (image.currentSrc || image.src) ? (image.currentSrc || image.src) : 'sinemaku-logo';
    }

    function toDataUrl(image) {
        if (!image || !image.complete || !image.naturalWidth || !image.naturalHeight) {
            return null;
        }

        var key = cacheKey(image);
        if (logoCache[key]) {
            return logoCache[key];
        }

        var canvas = document.createElement('canvas');
        canvas.width = image.naturalWidth;
        canvas.height = image.naturalHeight;
        var context = canvas.getContext('2d');

        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.drawImage(image, 0, 0, canvas.width, canvas.height);

        logoCache[key] = canvas.toDataURL('image/jpeg', 0.96);
        return logoCache[key];
    }

    function add(doc, image, x, y, width, height) {
        var dataUrl = toDataUrl(image);
        if (!dataUrl) {
            return false;
        }

        doc.addImage(dataUrl, 'JPEG', x, y, width, height, undefined, 'FAST');
        return true;
    }

    window.SinemakuPdfLogo = {
        add: add,
        toDataUrl: toDataUrl
    };
})(window);
