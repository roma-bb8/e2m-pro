LocalStorage = Class.create();
LocalStorage.prototype = {

    // ---------------------------------------

    data: {},
    M2EPRO_STORAGE_KEY: 'm2epro_data',

    // ---------------------------------------

    initialize: function()
    {
        var data = localStorage.getItem(this.M2EPRO_STORAGE_KEY);

        if (data === null) {
            localStorage.setItem(this.M2EPRO_STORAGE_KEY, JSON.stringify({}));
            return;
        }

        try {
            this.data = JSON.parse(data);
        } catch (exception) {
            localStorage.setItem(this.M2EPRO_STORAGE_KEY, JSON.stringify({}));
        }
    },

    // ---------------------------------------

    set: function(key, value)
    {
        var self = this;

        self.data[key] = value;
        return localStorage.setItem(self.M2EPRO_STORAGE_KEY, JSON.stringify(self.data));
    },

    get: function (key)
    {
        var self = this;

        if (typeof self.data[key] === 'undefined') {
            return null;
        }

        return self.data[key];
    },

    remove: function(key)
    {
        var self = this;

        if (typeof self.data[key] === 'undefined') {
            return false;
        }

        delete self.data[key];
        localStorage.setItem(self.M2EPRO_STORAGE_KEY, JSON.stringify(self.data));
        return true;
    },

    removeAllByPrefix: function(prefix)
    {
        var self = this;

        $H(self.data).each(function(item) {
            if (item.key.indexOf(prefix) === -1) {
                return;
            }

            delete self.data[item.key];
        });

        localStorage.setItem(self.M2EPRO_STORAGE_KEY, JSON.stringify(self.data));
    },

    removeAllByPostfix: function(postfix)
    {
        var self = this;

        $H(self.data).each(function(item) {
            if (item.key.indexOf(postfix) === -1) {
                return;
            }

            delete self.data[item.key];
        });

        localStorage.setItem(self.M2EPRO_STORAGE_KEY, JSON.stringify(self.data));
    },

    removeAll: function ()
    {
        var self = this;

        localStorage.setItem(self.M2EPRO_STORAGE_KEY, JSON.stringify({}));
        return true;
    }

    // ---------------------------------------
};

function utf8_encode (str_data)
{
    str_data = str_data.replace(/\r\n/g,"\n");
    var utftext = "";

    for (var n = 0; n < str_data.length; n++) {
        var c = str_data.charCodeAt(n);
        if (c < 128) {
            utftext += String.fromCharCode(c);
        } else if ((c > 127) && (c < 2048)) {
            utftext += String.fromCharCode((c >> 6) | 192);
            utftext += String.fromCharCode((c & 63) | 128);
        } else {
            utftext += String.fromCharCode((c >> 12) | 224);
            utftext += String.fromCharCode(((c >> 6) & 63) | 128);
            utftext += String.fromCharCode((c & 63) | 128);
        }
    }

    return utftext;
}

function md5 (str)
{
    var RotateLeft = function(lValue, iShiftBits) {
        return (lValue<<iShiftBits) | (lValue>>>(32-iShiftBits));
    };

    var AddUnsigned = function(lX,lY) {
        var lX4,lY4,lX8,lY8,lResult;
        lX8 = (lX & 0x80000000);
        lY8 = (lY & 0x80000000);
        lX4 = (lX & 0x40000000);
        lY4 = (lY & 0x40000000);
        lResult = (lX & 0x3FFFFFFF)+(lY & 0x3FFFFFFF);
        if (lX4 & lY4) {
            return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
        }
        if (lX4 | lY4) {
            if (lResult & 0x40000000) {
                return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
            } else {
                return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
            }
        } else {
            return (lResult ^ lX8 ^ lY8);
        }
    };

    var F = function(x,y,z) { return (x & y) | ((~x) & z); };
    var G = function(x,y,z) { return (x & z) | (y & (~z)); };
    var H = function(x,y,z) { return (x ^ y ^ z); };
    var I = function(x,y,z) { return (y ^ (x | (~z))); };

    var FF = function(a,b,c,d,x,s,ac) {
        a = AddUnsigned(a, AddUnsigned(AddUnsigned(F(b, c, d), x), ac));
        return AddUnsigned(RotateLeft(a, s), b);
    };

    var GG = function(a,b,c,d,x,s,ac) {
        a = AddUnsigned(a, AddUnsigned(AddUnsigned(G(b, c, d), x), ac));
        return AddUnsigned(RotateLeft(a, s), b);
    };

    var HH = function(a,b,c,d,x,s,ac) {
        a = AddUnsigned(a, AddUnsigned(AddUnsigned(H(b, c, d), x), ac));
        return AddUnsigned(RotateLeft(a, s), b);
    };

    var II = function(a,b,c,d,x,s,ac) {
        a = AddUnsigned(a, AddUnsigned(AddUnsigned(I(b, c, d), x), ac));
        return AddUnsigned(RotateLeft(a, s), b);
    };

    var ConvertToWordArray = function(str) {
        var lWordCount;
        var lMessageLength = str.length;
        var lNumberOfWords_temp1=lMessageLength + 8;
        var lNumberOfWords_temp2=(lNumberOfWords_temp1-(lNumberOfWords_temp1 % 64))/64;
        var lNumberOfWords = (lNumberOfWords_temp2+1)*16;
        var lWordArray=Array(lNumberOfWords-1);
        var lBytePosition = 0;
        var lByteCount = 0;
        while (lByteCount < lMessageLength) {
            lWordCount = (lByteCount-(lByteCount % 4))/4;
            lBytePosition = (lByteCount % 4)*8;
            lWordArray[lWordCount] = (lWordArray[lWordCount] | (str.charCodeAt(lByteCount)<<lBytePosition));
            lByteCount++;
        }
        lWordCount = (lByteCount-(lByteCount % 4))/4;
        lBytePosition = (lByteCount % 4)*8;
        lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80<<lBytePosition);
        lWordArray[lNumberOfWords-2] = lMessageLength<<3;
        lWordArray[lNumberOfWords-1] = lMessageLength>>>29;
        return lWordArray;
    };

    var WordToHex = function(lValue) {
        var WordToHexValue="",WordToHexValue_temp="",lByte,lCount;
        for (lCount = 0;lCount<=3;lCount++) {
            lByte = (lValue>>>(lCount*8)) & 255;
            WordToHexValue_temp = "0" + lByte.toString(16);
            WordToHexValue = WordToHexValue + WordToHexValue_temp.substr(WordToHexValue_temp.length-2,2);
        }
        return WordToHexValue;
    };

    var x=Array();
    var k,AA,BB,CC,DD,a,b,c,d;
    var S11=7, S12=12, S13=17, S14=22;
    var S21=5, S22=9 , S23=14, S24=20;
    var S31=4, S32=11, S33=16, S34=23;
    var S41=6, S42=10, S43=15, S44=21;

    str = this.utf8_encode(str);
    x = ConvertToWordArray(str);
    a = 0x67452301; b = 0xEFCDAB89; c = 0x98BADCFE; d = 0x10325476;

    for (k=0;k<x.length;k+=16) {
        AA=a; BB=b; CC=c; DD=d;
        a=FF(a,b,c,d,x[k+0], S11,0xD76AA478);
        d=FF(d,a,b,c,x[k+1], S12,0xE8C7B756);
        c=FF(c,d,a,b,x[k+2], S13,0x242070DB);
        b=FF(b,c,d,a,x[k+3], S14,0xC1BDCEEE);
        a=FF(a,b,c,d,x[k+4], S11,0xF57C0FAF);
        d=FF(d,a,b,c,x[k+5], S12,0x4787C62A);
        c=FF(c,d,a,b,x[k+6], S13,0xA8304613);
        b=FF(b,c,d,a,x[k+7], S14,0xFD469501);
        a=FF(a,b,c,d,x[k+8], S11,0x698098D8);
        d=FF(d,a,b,c,x[k+9], S12,0x8B44F7AF);
        c=FF(c,d,a,b,x[k+10],S13,0xFFFF5BB1);
        b=FF(b,c,d,a,x[k+11],S14,0x895CD7BE);
        a=FF(a,b,c,d,x[k+12],S11,0x6B901122);
        d=FF(d,a,b,c,x[k+13],S12,0xFD987193);
        c=FF(c,d,a,b,x[k+14],S13,0xA679438E);
        b=FF(b,c,d,a,x[k+15],S14,0x49B40821);
        a=GG(a,b,c,d,x[k+1], S21,0xF61E2562);
        d=GG(d,a,b,c,x[k+6], S22,0xC040B340);
        c=GG(c,d,a,b,x[k+11],S23,0x265E5A51);
        b=GG(b,c,d,a,x[k+0], S24,0xE9B6C7AA);
        a=GG(a,b,c,d,x[k+5], S21,0xD62F105D);
        d=GG(d,a,b,c,x[k+10],S22,0x2441453);
        c=GG(c,d,a,b,x[k+15],S23,0xD8A1E681);
        b=GG(b,c,d,a,x[k+4], S24,0xE7D3FBC8);
        a=GG(a,b,c,d,x[k+9], S21,0x21E1CDE6);
        d=GG(d,a,b,c,x[k+14],S22,0xC33707D6);
        c=GG(c,d,a,b,x[k+3], S23,0xF4D50D87);
        b=GG(b,c,d,a,x[k+8], S24,0x455A14ED);
        a=GG(a,b,c,d,x[k+13],S21,0xA9E3E905);
        d=GG(d,a,b,c,x[k+2], S22,0xFCEFA3F8);
        c=GG(c,d,a,b,x[k+7], S23,0x676F02D9);
        b=GG(b,c,d,a,x[k+12],S24,0x8D2A4C8A);
        a=HH(a,b,c,d,x[k+5], S31,0xFFFA3942);
        d=HH(d,a,b,c,x[k+8], S32,0x8771F681);
        c=HH(c,d,a,b,x[k+11],S33,0x6D9D6122);
        b=HH(b,c,d,a,x[k+14],S34,0xFDE5380C);
        a=HH(a,b,c,d,x[k+1], S31,0xA4BEEA44);
        d=HH(d,a,b,c,x[k+4], S32,0x4BDECFA9);
        c=HH(c,d,a,b,x[k+7], S33,0xF6BB4B60);
        b=HH(b,c,d,a,x[k+10],S34,0xBEBFBC70);
        a=HH(a,b,c,d,x[k+13],S31,0x289B7EC6);
        d=HH(d,a,b,c,x[k+0], S32,0xEAA127FA);
        c=HH(c,d,a,b,x[k+3], S33,0xD4EF3085);
        b=HH(b,c,d,a,x[k+6], S34,0x4881D05);
        a=HH(a,b,c,d,x[k+9], S31,0xD9D4D039);
        d=HH(d,a,b,c,x[k+12],S32,0xE6DB99E5);
        c=HH(c,d,a,b,x[k+15],S33,0x1FA27CF8);
        b=HH(b,c,d,a,x[k+2], S34,0xC4AC5665);
        a=II(a,b,c,d,x[k+0], S41,0xF4292244);
        d=II(d,a,b,c,x[k+7], S42,0x432AFF97);
        c=II(c,d,a,b,x[k+14],S43,0xAB9423A7);
        b=II(b,c,d,a,x[k+5], S44,0xFC93A039);
        a=II(a,b,c,d,x[k+12],S41,0x655B59C3);
        d=II(d,a,b,c,x[k+3], S42,0x8F0CCC92);
        c=II(c,d,a,b,x[k+10],S43,0xFFEFF47D);
        b=II(b,c,d,a,x[k+1], S44,0x85845DD1);
        a=II(a,b,c,d,x[k+8], S41,0x6FA87E4F);
        d=II(d,a,b,c,x[k+15],S42,0xFE2CE6E0);
        c=II(c,d,a,b,x[k+6], S43,0xA3014314);
        b=II(b,c,d,a,x[k+13],S44,0x4E0811A1);
        a=II(a,b,c,d,x[k+4], S41,0xF7537E82);
        d=II(d,a,b,c,x[k+11],S42,0xBD3AF235);
        c=II(c,d,a,b,x[k+2], S43,0x2AD7D2BB);
        b=II(b,c,d,a,x[k+9], S44,0xEB86D391);
        a=AddUnsigned(a,AA);
        b=AddUnsigned(b,BB);
        c=AddUnsigned(c,CC);
        d=AddUnsigned(d,DD);
    }

    var temp = WordToHex(a)+WordToHex(b)+WordToHex(c)+WordToHex(d);

    return temp.toLowerCase();
}

MagentoBlock = Class.create();
MagentoBlock.prototype = {

    storageKeys: {
        prefix: 'm2e_mb_'
    },

    // ---------------------------------------

    initialize: function() {},

    // ---------------------------------------

    getHashedStorage: function(id)
    {
        var hashedStorageKey = this.storageKeys.prefix + md5(id).substr(0, 10);
        var resultStorage = LocalStorageObj.get(hashedStorageKey);

        if (resultStorage === null) {
            return '';
        }

        return resultStorage;
    },

    setHashedStorage: function(id)
    {
        var hashedStorageKey = this.storageKeys.prefix + md5(id).substr(0, 10);
        LocalStorageObj.set(hashedStorageKey, 1);
    },

    deleteHashedStorage: function(id)
    {
        var hashedStorageKey = this.storageKeys.prefix + md5(id).substr(0, 10);

        LocalStorageObj.remove(hashedStorageKey);
        LocalStorageObj.remove(id);
    },

    deleteAllHashedStorage: function()
    {
        LocalStorageObj.removeAllByPrefix(this.storageKeys.prefix);
    },

    // ---------------------------------------

    show: function(blockClass,init)
    {
        blockClass = blockClass || '';
        if (blockClass == '') {
            return false;
        }

        $$('div.'+blockClass)[0].select('div.entry-edit-head div.entry-edit-head-right div.block_visibility_changer').each(function(o) {
            o.remove();
        });
        $$('div.'+blockClass)[0].select('div.entry-edit-head div.entry-edit-head-right div.block_tips_changer').each(function(o) {
            o.show();
        });

        var tempObj = $$('div.'+blockClass)[0].select('div.entry-edit-head div.entry-edit-head-left')[0];
        tempObj.writeAttribute("onclick", "MagentoBlockObj.hide('"+blockClass+"','0');");

        var tempHtml = $$('div.'+blockClass)[0].select('div.entry-edit-head div.entry-edit-head-right')[0].innerHTML;
        var tempHtml2 = '<div class="block_visibility_changer collapseable" style="float: right; color: white; font-size: 11px; margin-left: 20px;">';
        tempHtml2 += '<a href="javascript:void(0);" onclick="MagentoBlockObj.hide(\''+blockClass+'\',\'0\');" style="width: 20px; border: 0px;" class="open">&nbsp;</a>';
        tempHtml2 += '</div>';
        $$('div.'+blockClass)[0].select('div.entry-edit-head div.entry-edit-head-right')[0].innerHTML = tempHtml2 + tempHtml;

        this.deleteHashedStorage(blockClass);

        if (init == '0') {
            $$('div.'+blockClass+' div.fieldset')[0].show();
        } else {
            $$('div.'+blockClass+' div.fieldset')[0].show();
        }

        $$('div.'+blockClass+' div.entry-edit-head')[0].setStyle({marginBottom: '0px'});
        $$('div.'+blockClass+' div.fieldset')[0].setStyle({marginBottom: '15px'});

        return true;
    },

    hide: function(blockClass,init)
    {
        blockClass = blockClass || '';
        if (blockClass == '') {
            return false;
        }

        $$('div.'+blockClass)[0].select('div.entry-edit-head div.entry-edit-head-right div.block_visibility_changer').each(function(o) {
            o.remove();
        });
        $$('div.'+blockClass)[0].select('div.entry-edit-head div.entry-edit-head-right div.block_tips_changer').each(function(o) {
            o.hide();
        });

        var tempObj = $$('div.'+blockClass)[0].select('div.entry-edit-head div.entry-edit-head-left')[0];
        tempObj.writeAttribute("onclick", "MagentoBlockObj.show('"+blockClass+"','0');");

        var tempHtml = $$('div.'+blockClass)[0].select('div.entry-edit-head div.entry-edit-head-right')[0].innerHTML;
        var tempHtml2 = '<div class="block_visibility_changer collapseable" style="float: right; color: white; font-size: 11px; margin-left: 20px;">';
        tempHtml2 += '<a href="javascript:void(0);" onclick="MagentoBlockObj.show(\''+blockClass+'\',\'0\');" style="width: 20px; border: 0px;">&nbsp;</a>';
        tempHtml2 += '</div>';
        $$('div.'+blockClass)[0].select('div.entry-edit-head div.entry-edit-head-right')[0].innerHTML = tempHtml2 + tempHtml;

        this.setHashedStorage(blockClass);

        if (init == '0') {
            $$('div.'+blockClass+' div.fieldset')[0].hide();
        } else {
            $$('div.'+blockClass+' div.fieldset')[0].hide();
        }

        $$('div.'+blockClass+' div.entry-edit-head')[0].setStyle({marginBottom: '15px'});
        $$('div.'+blockClass+' div.fieldset')[0].setStyle({marginBottom: '0px'});

        return true;
    },

    // ---------------------------------------

    observePrepareStart: function(blockObj)
    {
        var self = this;

        var tempCollapseable = blockObj.readAttribute('collapseable');
        if (typeof tempCollapseable == 'string' && tempCollapseable == 'no') {
            return;
        }

        var tempId = blockObj.readAttribute('id');
        if (typeof tempId != 'string') {
            tempId = 'magento_block_md5_' + md5(blockObj.innerHTML.replace(/[^A-Za-z]/g,''));
            blockObj.writeAttribute("id",tempId);
        }

        var blockClass = tempId + '_hide';
        blockObj.addClassName(blockClass);

        var tempObj = blockObj.select('div.entry-edit-head div.entry-edit-head-left')[0];
        tempObj.setStyle({cursor: 'pointer'});

        var isClosed = this.getHashedStorage(blockClass);

        if (isClosed == '' || isClosed == '0') {
            self.show(blockClass,'1');
        } else {
            self.hide(blockClass,'1');
        }
    }

    // ---------------------------------------
};

LocalStorageObj = new LocalStorage();
MagentoBlockObj = new MagentoBlock();
