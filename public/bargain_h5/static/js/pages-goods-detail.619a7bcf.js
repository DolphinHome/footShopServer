(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-goods-detail"],{"0962":function(t,e,a){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var i={props:["prop"],methods:{preview:function(t,e){uni.previewImage({current:e,urls:this.prop.thumb})}}};e.default=i},"14df":function(t,e,a){"use strict";a.r(e);var i=a("de37"),o=a("a758");for(var n in o)"default"!==n&&function(t){a.d(e,t,(function(){return o[t]}))}(n);a("2ddb");var s,r=a("f0c5"),d=Object(r["a"])(o["default"],i["b"],i["c"],!1,null,"43ef19e4",null,!1,i["a"],s);e["default"]=d.exports},"1c2c":function(t,e,a){"use strict";a("a4d3"),a("e01a"),a("99af"),Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var i={data:function(){return{curSwiper:1,url:"/api/v1/5da6e7013ccbf",downloadUrl:"",schemeUrl:"bentengjia://",pagePath:"/pages/goods/goodsdetail/goods-detail",swipers:[],goodsInfo:{},sku_id:0,sku_name:"",user_id:0,goods_id:0,body:"",comment:[],comment_total:0,activityInfo:null,shareCode:"",activity_id:0,share_sign:""}},onLoad:function(t){var e=t.goods_id,a=t.sku_id,i=void 0===a?0:a,o=t.activity_id,n=void 0===o?0:o,s=t.apk_download_url,r=t.user_id,d=t.share_sign;this.goods_id=e,this.sku_id=i||0,this.activity_id=n||0,this.downloadUrl=s,this.user_id=r,this.share_sign=d,this.loadData()},methods:{changeSwiper:function(t){this.curSwiper=t.detail.current+1},loadData:function(){var t=this,e={goods_id:this.goods_id};this.sku_id&&(e.sku_id=this.sku_id),uni.request({url:this.url,data:e,header:{"content-type":"application/x-www-form-urlencoded"},method:"GET",success:function(e){if(200==e.statusCode&&1==e.data.code){var a=e.data.data;console.log("Goods Detail",a),t.swipers=a.images,t.goodsInfo={name:a.name,price:a.shop_price,market_price:a.market_price,discounts:a.discounts||0,thumb:a.thumb,description:a.description,is_collect:a.is_collect,share_award_money:a.share_award_money||0,number:1,stock:a.stock||0,totalStock:a.stock||0,sales_sum:a.sales_sum,is_spec:a.is_spec},t.limit=a.limit||0,t.body=a.body,t.comment=a.comment||[],t.comment_total=a.comment_total||0}},fail:function(t){console.error(t)},complete:function(){uni.stopPullDownRefresh()}})},downloadAPK:function(){this.downloadUrl?window.location.href=this.downloadUrl:uni.showToast({icon:"none",title:"应用暂未上架!"})},openAPK:function(){window.location.href="".concat(this.schemeUrl,"?path=").concat(this.pagePath,"&goods_id=").concat(this.goods_id,"&sku_id=").concat(this.sku_id,"&activity_id=").concat(this.activity_id,"&user_id=").concat(this.user_id,"&share_sign=").concat(this.share_sign)}},onPullDownRefresh:function(){this.loadData()}};e.default=i},2379:function(t,e,a){"use strict";var i;a.d(e,"b",(function(){return o})),a.d(e,"c",(function(){return n})),a.d(e,"a",(function(){return i}));var o=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("v-uni-view",{staticClass:"evaluate-box solid-bottom"},[a("v-uni-view",{staticClass:"padding-tb-sm flex justify-between align-center"},[a("v-uni-view",{staticClass:"flex"},[a("v-uni-view",{staticClass:"block-80"},[a("v-uni-image",{staticClass:"round",attrs:{src:t.prop.head_img,mode:"aspectFill","lazy-load":!0}})],1),a("v-uni-view",{staticClass:"margin-left-sm flex flex-direction justify-between"},[a("v-uni-view",{staticClass:"text-df text-333"},[t._v(t._s(t.prop.user_nickname))]),a("v-uni-view",{staticClass:"text-sm text-999"},[t._v(t._s(t.prop.create_time))])],1)],1),a("v-uni-view",[t._l(5,(function(e,i){return[a("v-uni-image",{key:i+"_0",staticClass:"block-24",attrs:{src:i+1<=t.prop.star?"/static/shop/stard2.png":"/static/shop/star2.png",mode:"aspectFill"}})]}))],2)],1),a("v-uni-view",{staticClass:"padding-bottom-sm padding-top-sm text-df text-333 word-break-all"},[t._v(t._s(t.prop.content))]),0!=t.prop.thumb.length?a("v-uni-view",{staticClass:"padding-top-xs padding-bottom-sm"},[a("v-uni-view",{staticClass:"grid col-4"},[t._l(t.prop.thumb,(function(e,i){return[a("v-uni-view",{key:i+"_0",staticClass:"padding-xs wrap-box",on:{click:function(a){arguments[0]=a=t.$handleEvent(a),t.preview(e,i)}}},[a("v-uni-view",{staticClass:"square-wrap"},[a("v-uni-view",{staticClass:"square-box"},[a("v-uni-image",{attrs:{src:e,"lazy-load":!0,mode:"aspectFill"}})],1)],1)],1)]}))],2)],1):t._e()],1)},n=[]},"2ddb":function(t,e,a){"use strict";var i=a("b7ae"),o=a.n(i);o.a},"35a5":function(t,e,a){var i=a("24fb");e=i(!1),e.push([t.i,'@charset "UTF-8";\n/**\n * 这里是uni-app内置的常用样式变量\n *\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\n *\n */\n/**\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\n *\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\n */\n/* 颜色变量 */\n/**\n * 公共样式\n * 补充一些colorUI没有但是设计图常用的样式\n * 一些兼容性样式等\n */.text-333[data-v-2a1e4920]{color:#333}.text-666[data-v-2a1e4920]{color:#666}.text-999[data-v-2a1e4920]{color:#999}.text-bf[data-v-2a1e4920]{color:#bfbfbf}.text-through[data-v-2a1e4920]{text-decoration:line-through}.text-cut-2[data-v-2a1e4920],\n.text-cut-3[data-v-2a1e4920],\n.text-cut-4[data-v-2a1e4920],\n.text-cut-5[data-v-2a1e4920]{display:-webkit-box;-webkit-box-orient:vertical;overflow:hidden;word-break:break-all}.text-cut-2[data-v-2a1e4920]{-webkit-line-clamp:2}.text-cut-3[data-v-2a1e4920]{-webkit-line-clamp:3}.text-cut-4[data-v-2a1e4920]{-webkit-line-clamp:4}.text-cut-5[data-v-2a1e4920]{-webkit-line-clamp:5}.height-88[data-v-2a1e4920]{height:%?88?%}.height-100[data-v-2a1e4920]{height:%?100?%}.safeArea-bottom[data-v-2a1e4920]{padding-bottom:0;padding-bottom:constant(safe-area-inset-bottom);padding-bottom:env(safe-area-inset-bottom)}.bg-theme[data-v-2a1e4920]{background-color:#ff292c!important}.text-theme[data-v-2a1e4920], .line-theme[data-v-2a1e4920], .lines-theme[data-v-2a1e4920]{color:#ff292c!important}.line-theme[data-v-2a1e4920]::after, .lines-theme[data-v-2a1e4920]::after{border-color:#ff292c!important}.overHidden[data-v-2a1e4920]{overflow:hidden}.word-break-all[data-v-2a1e4920]{word-break:break-all}uni-switch.theme[checked] .wx-switch-input[data-v-2a1e4920],\nuni-checkbox.theme[checked] .wx-checkbox-input[data-v-2a1e4920],\nuni-radio.theme[checked] .wx-radio-input[data-v-2a1e4920],\nuni-switch.theme.checked .uni-switch-input[data-v-2a1e4920],\nuni-checkbox.theme.checked .uni-checkbox-input[data-v-2a1e4920],\nuni-radio.theme.checked .uni-radio-input[data-v-2a1e4920]{background-color:#ff292c!important;border-color:#ff292c!important;color:#fff!important}\n/* 行为相关颜色 */\n/* 文字基本颜色 */\n/* 背景颜色 */\n/* 边框颜色 */\n/* 尺寸变量 */\n/* 文字尺寸 */\n/* 图片尺寸 */\n/* Border Radius */\n/* 水平间距 */\n/* 垂直间距 */\n/* 透明度 */\n/* 文章场景相关 */.wrap-box[data-v-2a1e4920]{background-color:#fff}.wrap-box .square-wrap[data-v-2a1e4920]{width:100%;height:0;padding-bottom:100%;-webkit-box-sizing:border-box;box-sizing:border-box;position:relative}.wrap-box .square-wrap .square-box[data-v-2a1e4920]{position:absolute;top:0;right:0;bottom:0;left:0;-webkit-border-radius:%?8?%;border-radius:%?8?%}.wrap-box .square-wrap .square-box uni-image[data-v-2a1e4920]{width:100%;height:100%;-webkit-border-radius:%?8?%;border-radius:%?8?%}.block-80[data-v-2a1e4920]{width:%?80?%;height:%?80?%;min-width:%?80?%;min-height:%?80?%;background-color:#eee;-webkit-border-radius:50%;border-radius:50%}.block-80 uni-image[data-v-2a1e4920]{width:100%;height:100%}.block-24[data-v-2a1e4920]{width:%?24?%;height:%?24?%;min-width:%?24?%;min-height:%?24?%}',""]),t.exports=e},"3ad7":function(t,e,a){"use strict";a.r(e);var i=a("2379"),o=a("c601");for(var n in o)"default"!==n&&function(t){a.d(e,t,(function(){return o[t]}))}(n);a("ce80");var s,r=a("f0c5"),d=Object(r["a"])(o["default"],i["b"],i["c"],!1,null,"2a1e4920",null,!1,i["a"],s);e["default"]=d.exports},8323:function(t,e,a){var i=a("24fb");e=i(!1),e.push([t.i,'@charset "UTF-8";\n/**\n * 这里是uni-app内置的常用样式变量\n *\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\n *\n */\n/**\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\n *\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\n */\n/* 颜色变量 */\n/**\n * 公共样式\n * 补充一些colorUI没有但是设计图常用的样式\n * 一些兼容性样式等\n */.text-333[data-v-43ef19e4]{color:#333}.text-666[data-v-43ef19e4]{color:#666}.text-999[data-v-43ef19e4]{color:#999}.text-bf[data-v-43ef19e4]{color:#bfbfbf}.text-through[data-v-43ef19e4]{text-decoration:line-through}.text-cut-2[data-v-43ef19e4],\n.text-cut-3[data-v-43ef19e4],\n.text-cut-4[data-v-43ef19e4],\n.text-cut-5[data-v-43ef19e4]{display:-webkit-box;-webkit-box-orient:vertical;overflow:hidden;word-break:break-all}.text-cut-2[data-v-43ef19e4]{-webkit-line-clamp:2}.text-cut-3[data-v-43ef19e4]{-webkit-line-clamp:3}.text-cut-4[data-v-43ef19e4]{-webkit-line-clamp:4}.text-cut-5[data-v-43ef19e4]{-webkit-line-clamp:5}.height-88[data-v-43ef19e4]{height:%?88?%}.height-100[data-v-43ef19e4]{height:%?100?%}.safeArea-bottom[data-v-43ef19e4]{padding-bottom:0;padding-bottom:constant(safe-area-inset-bottom);padding-bottom:env(safe-area-inset-bottom)}.bg-theme[data-v-43ef19e4]{background-color:#ff292c!important}.text-theme[data-v-43ef19e4], .line-theme[data-v-43ef19e4], .lines-theme[data-v-43ef19e4]{color:#ff292c!important}.line-theme[data-v-43ef19e4]::after, .lines-theme[data-v-43ef19e4]::after{border-color:#ff292c!important}.overHidden[data-v-43ef19e4]{overflow:hidden}.word-break-all[data-v-43ef19e4]{word-break:break-all}uni-switch.theme[checked] .wx-switch-input[data-v-43ef19e4],\nuni-checkbox.theme[checked] .wx-checkbox-input[data-v-43ef19e4],\nuni-radio.theme[checked] .wx-radio-input[data-v-43ef19e4],\nuni-switch.theme.checked .uni-switch-input[data-v-43ef19e4],\nuni-checkbox.theme.checked .uni-checkbox-input[data-v-43ef19e4],\nuni-radio.theme.checked .uni-radio-input[data-v-43ef19e4]{background-color:#ff292c!important;border-color:#ff292c!important;color:#fff!important}\n/* 行为相关颜色 */\n/* 文字基本颜色 */\n/* 背景颜色 */\n/* 边框颜色 */\n/* 尺寸变量 */\n/* 文字尺寸 */\n/* 图片尺寸 */\n/* Border Radius */\n/* 水平间距 */\n/* 垂直间距 */\n/* 透明度 */\n/* 文章场景相关 */.page[data-v-43ef19e4]{min-height:100vh;background-color:#f5f6f7;padding-bottom:%?120?%;padding-bottom:calc(%?120?% + constant(safe-area-inset-bottom));padding-bottom:calc(%?120?% + env(safe-area-inset-bottom))}.banners[data-v-43ef19e4]{height:%?750?%;position:relative}.banners .carousel[data-v-43ef19e4]{width:100%;height:100%}.banners .carousel uni-image[data-v-43ef19e4]{width:100%;height:100%}.banners .pagination[data-v-43ef19e4]{width:%?80?%;height:%?40?%;-webkit-border-radius:%?40?%;border-radius:%?40?%;background-color:rgba(0,0,0,.5);position:absolute;right:%?32?%;bottom:%?32?%;line-height:%?40?%;font-size:%?28?%;text-align:center;color:hsla(0,0%,100%,.8)}.prices[data-v-43ef19e4]{height:%?112?%;background-color:#fff}.prices .box[data-v-43ef19e4]{height:100%;background:-webkit-gradient(linear,left top,right top,from(#ff154f),to(#f12228));background:-webkit-linear-gradient(left,#ff154f,#f12228);background:linear-gradient(90deg,#ff154f,#f12228);padding:0 %?32?%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center}.infos[data-v-43ef19e4]{-webkit-border-radius:0 0 %?20?% %?20?%;border-radius:0 0 %?20?% %?20?%}.evaluates .title[data-v-43ef19e4]{height:%?100?%}.shopinfos .title[data-v-43ef19e4]{height:%?260?%;background-repeat:no-repeat;background-size:cover;padding:%?30?%;-webkit-border-radius:%?20?% %?20?% 0 0;border-radius:%?20?% %?20?% 0 0}.shopinfos .title .tag[data-v-43ef19e4]{padding:%?5?% %?10?%;font-size:%?16?%;background-color:rgba(255,0,0,.6);-webkit-border-radius:%?100?%;border-radius:%?100?%;color:#fff}.shopinfos .title .tag + .tag[data-v-43ef19e4]{margin-left:%?10?%}.shopinfos .title .action[data-v-43ef19e4]{width:%?160?%;height:%?60?%;line-height:%?60?%;text-align:center;-webkit-border-radius:%?60?%;border-radius:%?60?%;background-color:rgba(0,0,255,.3);color:#fff;font-size:%?24?%}.shopinfos .recommend .wrap[data-v-43ef19e4]{width:100%}.shopinfos .recommend .wrap .list[data-v-43ef19e4]{display:-webkit-box;display:-webkit-flex;display:flex;align-props:flex-start}.shopinfos .recommend .wrap .list .goods-item + .goods-item[data-v-43ef19e4]{margin-left:%?20?%}.details .title[data-v-43ef19e4]{height:%?120?%;font-size:%?40?%;color:#333;font-weight:700;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.tools[data-v-43ef19e4]{position:fixed;bottom:0;left:0;right:0;padding-bottom:0;padding-bottom:constant(safe-area-inset-bottom);padding-bottom:env(safe-area-inset-bottom);background-color:#fff;-webkit-box-shadow:0 -1px 0 #eee;box-shadow:0 -1px 0 #eee}.tools .toolbar[data-v-43ef19e4]{height:%?100?%;display:-webkit-box;display:-webkit-flex;display:flex;color:#fff;font-size:%?42?%;line-height:%?100?%;text-align:center}.tools .toolbar .toolbar-left[data-v-43ef19e4]{-webkit-box-flex:1;-webkit-flex:1;flex:1;background-color:#fd824b}.tools .toolbar .toolbar-right[data-v-43ef19e4]{-webkit-box-flex:1;-webkit-flex:1;flex:1;background-color:#f02523}.block-30[data-v-43ef19e4]{width:%?30?%;height:%?28?%}.block-32[data-v-43ef19e4]{width:%?32?%;height:%?32?%}.radius-20[data-v-43ef19e4]{-webkit-border-radius:%?20?%;border-radius:%?20?%}.block-90[data-v-43ef19e4]{width:%?90?%;height:%?90?%;min-width:%?90?%;min-height:%?90?%}.block-90 uni-image[data-v-43ef19e4]{width:100%;height:100%}.block-220[data-v-43ef19e4]{width:%?220?%;height:%?220?%;min-width:%?220?%;min-height:%?220?%}.block-220 uni-image[data-v-43ef19e4]{width:100%;height:100%}',""]),t.exports=e},a758:function(t,e,a){"use strict";a.r(e);var i=a("1c2c"),o=a.n(i);for(var n in i)"default"!==n&&function(t){a.d(e,t,(function(){return i[t]}))}(n);e["default"]=o.a},b7ae:function(t,e,a){var i=a("8323");"string"===typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);var o=a("4f06").default;o("b0deb79a",i,!0,{sourceMap:!1,shadowMode:!1})},bac6:function(t,e,a){var i=a("35a5");"string"===typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);var o=a("4f06").default;o("157e7bf3",i,!0,{sourceMap:!1,shadowMode:!1})},c601:function(t,e,a){"use strict";a.r(e);var i=a("0962"),o=a.n(i);for(var n in i)"default"!==n&&function(t){a.d(e,t,(function(){return i[t]}))}(n);e["default"]=o.a},ce80:function(t,e,a){"use strict";var i=a("bac6"),o=a.n(i);o.a},de37:function(t,e,a){"use strict";a.d(e,"b",(function(){return o})),a.d(e,"c",(function(){return n})),a.d(e,"a",(function(){return i}));var i={fuEvaluate:a("3ad7").default},o=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("v-uni-view",{staticClass:"page"},[a("v-uni-view",{staticClass:"banners"},[a("v-uni-swiper",{staticClass:"carousel",attrs:{"indicator-dots":!1,circular:!0},on:{change:function(e){arguments[0]=e=t.$handleEvent(e),t.changeSwiper.apply(void 0,arguments)}}},[t._l(t.swipers,(function(t,e){return[a("v-uni-swiper-item",[a("v-uni-image",{attrs:{src:t,mode:"aspectFill","lazy-load":!0}})],1)]}))],2),a("v-uni-view",{staticClass:"pagination"},[t._v(t._s(t.curSwiper)+"/"+t._s(t.swipers.length))])],1),a("v-uni-view",{staticClass:"prices"},[a("v-uni-view",{staticClass:"box"},[a("v-uni-view",{staticClass:"text-white"},[a("v-uni-text",[a("v-uni-text",{staticClass:"text-price text-xl"}),a("v-uni-text",{staticClass:"text-xxl"},[t._v(t._s(t.goodsInfo.price))])],1),a("v-uni-text",{staticClass:"text-through margin-left-sm"},[a("v-uni-text",{staticClass:"text-price text-lg"}),a("v-uni-text",{staticClass:"text-lg"},[t._v(t._s(t.goodsInfo.market_price))])],1)],1)],1)],1),a("v-uni-view",{staticClass:"infos padding bg-white"},[a("v-uni-view",{staticClass:"text-lg text-333 word-break-all text-cut-2"},[t._v(t._s(t.goodsInfo.name))]),a("v-uni-view",{staticClass:"flex justify-between align-center margin-top-sm"},[a("v-uni-text",{staticClass:"text-df text-999 word-break-all text-cut-2"},[t._v(t._s(t.goodsInfo.description))])],1),a("v-uni-view",{staticClass:"margin-top-sm text-sm text-999"},[a("v-uni-text",[t._v("库存: "+t._s(t.goodsInfo.totalStock))]),a("v-uni-text",{staticClass:"margin-left"},[t._v("销量: "+t._s(t.goodsInfo.sales_sum))])],1)],1),a("v-uni-view",{staticClass:"evaluates margin-top-sm radius-20 bg-white padding-lr"},[a("v-uni-view",{staticClass:"title flex justify-between align-center"},[a("v-uni-text",[a("v-uni-text",{staticClass:"text-lg text-333"},[t._v("商品评价")]),a("v-uni-text",{staticClass:"text-333 text-df margin-left-xs"},[t._v("("+t._s(t.comment_total)+")")])],1),a("v-uni-text",{on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.navToComment.apply(void 0,arguments)}}},[a("v-uni-text",{staticClass:"text-sm text-999"},[t._v("查看全部")]),a("v-uni-text",{staticClass:"cuIcon-right text-999 margin-left-xs",staticStyle:{"font-size":"24rpx"}})],1)],1),t.comment.length>0?a("v-uni-view",{staticClass:"content"},[t._l(t.comment,(function(t,e){return[a("fu-evaluate",{key:e+"_0",attrs:{prop:t}})]}))],2):t._e()],1),a("v-uni-view",{staticClass:"details radius-20 bg-white margin-top-sm",attrs:{id:"details"}},[a("v-uni-view",{staticClass:"title"},[a("v-uni-view",{staticClass:"icon icon1"}),a("v-uni-view",{staticClass:"margin-lr"},[t._v("商品详情")]),a("v-uni-view",{staticClass:"icon icon2"})],1),a("v-uni-view",{staticClass:"padding-lr padding-bottom word-break-all"},[a("v-uni-view",{domProps:{innerHTML:t._s(t.body)}})],1)],1),a("v-uni-view",{staticClass:"tools"},[a("v-uni-view",{staticClass:"toolbar"},[a("v-uni-view",{staticClass:"toolbar-left",on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.downloadAPK.apply(void 0,arguments)}}},[t._v("下载APP")]),a("v-uni-view",{staticClass:"toolbar-right",on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.openAPK.apply(void 0,arguments)}}},[t._v("打开APP")])],1)],1)],1)},n=[]}}]);