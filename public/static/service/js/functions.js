// 转义聊天内容中的特殊字符
function replaceContent(content) {
    // 支持的html标签
    var html = function (end) {
        return new RegExp('\\n*\\[' + (end || '') + '(pre|div|span|p|table|thead|th|tbody|tr|td|ul|li|ol|li|dl|dt|dd|h2|h3|h4|h5)([\\s\\S]*?)\\]\\n*', 'g');
    };
    content = (content || '').replace(/&(?!#?[a-zA-Z0-9]+;)/g, '&amp;')
        // .replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/'/g, '&#39;').replace(/"/g, '&quot;') // XSS
        .replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&#39;/g, "'").replace(/&quot;/g, '"') // XSS
        .replace(/@(\S+)(\s+?|$)/g, '@<a href="javascript:;">$1</a>$2') // 转义@

        .replace(/emoji\[([^\s\[\]]+?)\]/g, function (face) {  // 转义表情
            var alt = face.replace(/^emoji/g, '');
            return '<img style="width:24px;height:24px;" alt="' + alt + '" title="' + alt + '" src="' + faces[alt] + '">';
        })
        .replace(/img\[([^\s]+?)\]/g, function (img) {  // 转义图片
            return '<img class="layui-whisper-photos" src="' + img.replace(/(^img\[)|(\]$)/g, '') + '" width="100px" height="100px">';
        })
        .replace(/file\([\s\S]+?\)\[[\s\S]*?\]/g, function (str) { // 转义文件
            var href = (str.match(/file\(([\s\S]+?)\)\[/) || [])[1];
            var text = (str.match(/\)\[([\s\S]*?)\]/) || [])[1];
            if (!href) return str;
            return '<a class="layui-whisper-file" href="' + href + '" download target="_blank"><i class="layui-icon">&#xe61e;</i><cite>' + (text || href) + '</cite></a>';
        })
        .replace(/a\([\s\S]+?\)\[[\s\S]*?\]/g, function (str) { // 转义链接
            var href = (str.match(/a\(([\s\S]+?)\)\[/) || [])[1];
            var text = (str.match(/\)\[([\s\S]*?)\]/) || [])[1];
            if (!href) return str;
            return '<a href="' + href + '" target="_blank">' + (text || href) + '</a>';
        }).replace(html(), '\<$1 $2\>').replace(html('/'), '\</$1\>') // 转移HTML代码
        .replace(/\n/g, '<br>') // 转义换行
		console.log(content,'转义后的东西');
    return content;
};

// 表情替换
var faces = function () {
    var alt = getFacesIcon(), arr = {};
    layui.each(alt, function (index, item) {
		// if(Number(index) >= 100){
		// 	arr[item] = '/static/images/emoji/' + (index+100) + '.png';
		// }else{
		// 	arr[item] = '/static/images/emoji/' + (index+100) + '.gif';
		// }
        let num = index+1<10?'0'+(index+1):index+1;
        arr[item] = '/static/images/emoji/emoji_' + num + '.png';
    });
    return arr;
}();

// 表情对应数组
function getFacesIcon() {
    return ["[开心]","[高兴]","[哈哈]","[灿烂]","[大笑]","[汗臭]","[地板上笑]","[笑哭]","[微笑]","[颠倒]","[眨眼]","[面带笑容]","[晕笑]","[爱心笑]","[喜欢]","[开眼界]","[飞吻]","[亲亲]","[面带微笑]","[闭眼亲亲]","[笑脸亲亲]","[面对食物]","[吐舌]","[眨眼吐舌]","[赞尼]","[鬼脸]","[钱嘴]","[拥抱]","[偷笑]","[嘘]","[思考]","[闭嘴]","[翘眉毛]","[中性]","[无表情]","[无嘴脸]","[傻笑]","[无趣]","[白眼]","[尴尬]","[说谎]","[舒缓]","[沉思]","[沉睡]","[流口水]","[睡觉]","[感冒]","[发热]","[受伤]","[恶心]","[呕吐]","[打喷嚏]","[炎热]","[寒冷]","[毛茸茸]","[头晕]","[爆炸头]","[牛仔帽]","[聚会]","[酷]","[书呆子]","[观察]","[困惑]","[担心]","[皱眉]","[不开心]","[张嘴]","[寂静]","[惊讶]","[害羞]","[恳求]","[丧气]","[痛苦]","[可怕]","[焦虑]","[悲伤]","[哭泣]","[聚会]","[恐惧]","[极度困惑]","[执着]","[失望]","[垂头丧气]","[疲倦]","[十分疲倦]","[打哈欠]","[出气]","[愤怒]","[生气]","[咒骂]"]
}