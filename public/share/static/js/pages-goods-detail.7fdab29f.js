(window["webpackJsonp"] = window["webpackJsonp"] || []).push([["pages-goods-detail"], {
    "02f3": function (t, a, i) {
        var e = i("24fb");
        a = e(!1), a.push([t.i, '@charset "UTF-8";\n/**\n * 这里是uni-app内置的常用样式变量\n *\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\n *\n */\n/**\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\n *\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\n */\n/* 颜色变量 */.text-through[data-v-47836a20]{text-decoration:line-through}.word-break-all[data-v-47836a20]{word-wrap:break-word;white-space:normal;word-break:break-all}.text-333[data-v-47836a20]{color:#333}.text-666[data-v-47836a20]{color:#666}.text-999[data-v-47836a20]{color:#999}.text-sm[data-v-47836a20]{font-size:%?24?%}.text-df[data-v-47836a20]{font-size:%?28?%}.text-lg[data-v-47836a20]{font-size:%?32?%}.text-xl[data-v-47836a20]{font-size:%?36?%}.text-xxl[data-v-47836a20]{font-size:%?42?%}.text-white[data-v-47836a20]{color:#fff}.text-price[data-v-47836a20]::before{content:"¥";font-size:80%;margin-right:%?4?%}.margin-left-sm[data-v-47836a20]{margin-left:%?20?%}.padding[data-v-47836a20]{padding:%?30?%}.bg-white[data-v-47836a20]{background-color:#fff}.flex[data-v-47836a20]{display:-webkit-box;display:-webkit-flex;display:flex}.justify-between[data-v-47836a20]{-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between}.align-center[data-v-47836a20]{-webkit-box-align:center;-webkit-align-items:center;align-items:center}.margin-top-sm[data-v-47836a20]{margin-top:%?20?%}.text-cut-2[data-v-47836a20]{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden}.margin-left[data-v-47836a20]{margin-left:%?30?%}.justify-center[data-v-47836a20]{-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center}.padding-lr[data-v-47836a20]{padding:0 %?30?%}.margin-left-xs[data-v-47836a20]{margin-left:%?10?%}.solid-bottom[data-v-47836a20]{position:relative}.solid-bottom[data-v-47836a20]::after{content:" ";width:200%;height:200%;position:absolute;top:0;left:0;-webkit-border-radius:inherit;border-radius:inherit;-webkit-transform:scale(.5);transform:scale(.5);-webkit-transform-origin:0 0;transform-origin:0 0;pointer-events:none;-webkit-box-sizing:border-box;box-sizing:border-box}.solid-bottom[data-v-47836a20]::after{border-bottom:%?1?% solid rgba(0,0,0,.1)}.padding-tb-sm[data-v-47836a20]{padding:%?20?% 0}.round[data-v-47836a20]{-webkit-border-radius:%?5000?%;border-radius:%?5000?%}.padding-bottom-sm[data-v-47836a20]{padding-bottom:%?20?%}.padding-top-sm[data-v-47836a20]{padding-top:%?20?%}.padding-top-xs[data-v-47836a20]{padding-top:%?10?%}.padding-xs[data-v-47836a20]{padding:%?10?%}.grid[data-v-47836a20]{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-flex-wrap:wrap;flex-wrap:wrap}.grid.col-4.grid-square > uni-view[data-v-47836a20]{padding-bottom:calc((100% - %?60?%)/4);height:0;width:calc((100% - %?60?%)/4)}.grid.col-2.grid-square > uni-view[data-v-47836a20]:nth-child(2n),\n.grid.col-3.grid-square > uni-view[data-v-47836a20]:nth-child(3n),\n.grid.col-4.grid-square > uni-view[data-v-47836a20]:nth-child(4n),\n.grid.col-5.grid-square > uni-view[data-v-47836a20]:nth-child(5n){margin-right:0}.grid.col-4 > uni-view[data-v-47836a20]{width:25%}\n/* 行为相关颜色 */\n/* 文字基本颜色 */\n/* 背景颜色 */\n/* 边框颜色 */\n/* 尺寸变量 */\n/* 文字尺寸 */\n/* 图片尺寸 */\n/* Border Radius */\n/* 水平间距 */\n/* 垂直间距 */\n/* 透明度 */\n/* 文章场景相关 */.page[data-v-47836a20]{min-height:100vh;background-color:#f5f6f7;padding-bottom:%?120?%;padding-bottom:calc(%?120?% + constant(safe-area-inset-bottom));padding-bottom:calc(%?120?% + env(safe-area-inset-bottom))}.banners[data-v-47836a20]{height:%?750?%;position:relative}.banners .carousel[data-v-47836a20]{width:100%;height:100%}.banners .carousel uni-image[data-v-47836a20]{width:100%;height:100%}.banners .pagination[data-v-47836a20]{width:%?80?%;height:%?40?%;-webkit-border-radius:%?40?%;border-radius:%?40?%;background-color:rgba(0,0,0,.5);position:absolute;right:%?32?%;bottom:%?32?%;line-height:%?40?%;font-size:%?28?%;text-align:center;color:hsla(0,0%,100%,.8)}.prices[data-v-47836a20]{height:%?112?%;background-color:#fff}.prices .box[data-v-47836a20]{height:100%;background:-webkit-gradient(linear,left top,right top,from(#ff154f),to(#f12228));background:-webkit-linear-gradient(left,#ff154f,#f12228);background:linear-gradient(90deg,#ff154f,#f12228);padding:0 %?32?%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center}.infos[data-v-47836a20]{-webkit-border-radius:0 0 %?20?% %?20?%;border-radius:0 0 %?20?% %?20?%}.evaluates .title[data-v-47836a20]{height:%?100?%}.shopinfos .title[data-v-47836a20]{height:%?260?%;background-repeat:no-repeat;background-size:cover;padding:%?30?%;-webkit-border-radius:%?20?% %?20?% 0 0;border-radius:%?20?% %?20?% 0 0}.shopinfos .title .tag[data-v-47836a20]{padding:%?5?% %?10?%;font-size:%?16?%;background-color:rgba(255,0,0,.6);-webkit-border-radius:%?100?%;border-radius:%?100?%;color:#fff}.shopinfos .title .tag + .tag[data-v-47836a20]{margin-left:%?10?%}.shopinfos .title .action[data-v-47836a20]{width:%?160?%;height:%?60?%;line-height:%?60?%;text-align:center;-webkit-border-radius:%?60?%;border-radius:%?60?%;background-color:rgba(0,0,255,.3);color:#fff;font-size:%?24?%}.shopinfos .recommend .wrap[data-v-47836a20]{width:100%}.shopinfos .recommend .wrap .list[data-v-47836a20]{display:-webkit-box;display:-webkit-flex;display:flex;align-props:flex-start}.shopinfos .recommend .wrap .list .goods-item + .goods-item[data-v-47836a20]{margin-left:%?20?%}.details .title[data-v-47836a20]{height:%?120?%;font-size:%?40?%;color:#333;font-weight:700;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.tools[data-v-47836a20]{position:fixed;bottom:0;left:0;right:0;padding-bottom:0;padding-bottom:constant(safe-area-inset-bottom);padding-bottom:env(safe-area-inset-bottom);background-color:#fff;-webkit-box-shadow:0 -1px 0 #eee;box-shadow:0 -1px 0 #eee}.tools .toolbar[data-v-47836a20]{height:%?100?%;display:-webkit-box;display:-webkit-flex;display:flex;color:#fff;font-size:%?42?%;line-height:%?100?%;text-align:center}.tools .toolbar .toolbar-left[data-v-47836a20]{-webkit-box-flex:1;-webkit-flex:1;flex:1;background-color:#fd824b}.tools .toolbar .toolbar-right[data-v-47836a20]{-webkit-box-flex:1;-webkit-flex:1;flex:1;background-color:#f02523}.block-30[data-v-47836a20]{width:%?30?%;height:%?28?%}.block-32[data-v-47836a20]{width:%?32?%;height:%?32?%}.radius-20[data-v-47836a20]{-webkit-border-radius:%?20?%;border-radius:%?20?%}.block-90[data-v-47836a20]{width:%?90?%;height:%?90?%;min-width:%?90?%;min-height:%?90?%}.block-90 uni-image[data-v-47836a20]{width:100%;height:100%}.block-220[data-v-47836a20]{width:%?220?%;height:%?220?%;min-width:%?220?%;min-height:%?220?%}.block-220 uni-image[data-v-47836a20]{width:100%;height:100%}', ""]), t.exports = a
    }, "0962": function (t, a, i) {
        "use strict";
        Object.defineProperty(a, "__esModule", {value: !0}), a.default = void 0;
        var e = {
            props: ["prop"], methods: {
                preview: function (t, a) {
                    uni.previewImage({current: a, urls: this.prop.thumb})
                }
            }
        };
        a.default = e
    }, "14df": function (t, a, i) {
        "use strict";
        i.r(a);
        var e = i("90c5"), n = i("a758");
        for (var o in n) "default" !== o && function (t) {
            i.d(a, t, (function () {
                return n[t]
            }))
        }(o);
        i("3aa8");
        var s, r = i("f0c5"),
            d = Object(r["a"])(n["default"], e["b"], e["c"], !1, null, "47836a20", null, !1, e["a"], s);
        a["default"] = d.exports
    }, "1c2c": function (t, a, i) {
        "use strict";
        i("a4d3"), i("e01a"), i("99af"), Object.defineProperty(a, "__esModule", {value: !0}), a.default = void 0;
        var e = window.location.host, n = {
            data: function () {
                return {
                    curSwiper: 1,
                    url: "/api/v1/5da6e7013ccbf",
                    downloadUrl: "",
                    schemeUrl: "bentengjia://",
                    pagePath: "/pages/goods/goodsdetail/goods-detail",
                    swipers: [],
                    goodsInfo: {},
                    sku_id: 0,
                    sku_name: "",
                    user_id: 0,
                    goods_id: 0,
                    body: "",
                    comment: [],
                    comment_total: 0,
                    activityInfo: null,
                    shareCode: "",
                    activity_id: 0,
                    share_sign: ""
                }
            }, onLoad: function (t) {
                var a = t.goods_id, i = t.sku_id, e = void 0 === i ? 0 : i, n = t.activity_id, o = void 0 === n ? 0 : n,
                    s = t.apk_download_url, r = t.user_id, d = t.share_sign;
                this.goods_id = a, this.sku_id = e || 0, this.activity_id = o || 0, this.downloadUrl = s, this.user_id = r, this.share_sign = d, this.loadData()
            }, methods: {
                changeSwiper: function (t) {
                    this.curSwiper = t.detail.current + 1
                }, loadData: function () {
                    var t = this, a = {goods_id: this.goods_id};
                    this.sku_id && (a.sku_id = this.sku_id), uni.request({
                        url: this.url,
                        data: a,
                        header: {"content-type": "application/x-www-form-urlencoded"},
                        method: "GET",
                        success: function (a) {
                            if (200 == a.statusCode && 1 == a.data.code) {
                                var i = a.data.data;
                                console.log("Goods Detail", i), t.swipers = i.images, t.goodsInfo = {
                                    name: i.name,
                                    price: i.shop_price,
                                    market_price: i.market_price,
                                    discounts: i.discounts || 0,
                                    thumb: i.thumb,
                                    description: i.description,
                                    is_collect: i.is_collect,
                                    share_award_money: i.share_award_money || 0,
                                    number: 1,
                                    stock: i.stock || 0,
                                    totalStock: i.stock || 0,
                                    sales_sum: i.sales_sum,
                                    is_spec: i.is_spec
                                }, t.limit = i.limit || 0, t.body = i.body, t.comment = i.comment || [], t.comment_total = i.comment_total || 0
                            }
                        },
                        fail: function (t) {
                            console.error(t)
                        },
                        complete: function () {
                            uni.stopPullDownRefresh()
                        }
                    })
                }, downloadAPK: function () {
                    this.downloadUrl ? window.location.href = this.downloadUrl : uni.showToast({
                        icon: "none",
                        title: "应用暂未上架!"
                    })
                }, openAPK: function () {
                    window.location.href = "".concat(this.schemeUrl, "?path=").concat(this.pagePath, "&goods_id=").concat(this.goods_id, "&sku_id=").concat(this.sku_id, "&activity_id=").concat(this.activity_id, "&user_id=").concat(this.user_id, "&share_sign=").concat(this.share_sign)
                }
            }, onPullDownRefresh: function () {
                this.loadData()
            }
        };
        a.default = n
    }, 2379: function (t, a, i) {
        "use strict";
        var e;
        i.d(a, "b", (function () {
            return n
        })), i.d(a, "c", (function () {
            return o
        })), i.d(a, "a", (function () {
            return e
        }));
        var n = function () {
            var t = this, a = t.$createElement, i = t._self._c || a;
            return i("v-uni-view", {staticClass: "evaluate-box solid-bottom"}, [i("v-uni-view", {staticClass: "padding-tb-sm flex justify-between align-center"}, [i("v-uni-view", {staticClass: "flex"}, [i("v-uni-view", {staticClass: "block-80"}, [i("v-uni-image", {
                staticClass: "round",
                attrs: {src: t.prop.head_img, mode: "aspectFill", "lazy-load": !0}
            })], 1), i("v-uni-view", {staticClass: "margin-left-sm flex flex-direction justify-between"}, [i("v-uni-view", {staticClass: "text-df text-333"}, [t._v(t._s(t.prop.user_nickname))]), i("v-uni-view", {staticClass: "text-sm text-999"}, [t._v(t._s(t.prop.create_time))])], 1)], 1), i("v-uni-view", [t._l(5, (function (a, e) {
                return [i("v-uni-image", {
                    key: e + "_0",
                    staticClass: "block-24",
                    attrs: {
                        src: e + 1 <= t.prop.star ? "/static/shop/stard2.png" : "/static/shop/star2.png",
                        mode: "aspectFill"
                    }
                })]
            }))], 2)], 1), i("v-uni-view", {staticClass: "padding-bottom-sm padding-top-sm text-df text-333 word-break-all"}, [t._v(t._s(t.prop.content))]), 0 != t.prop.thumb.length ? i("v-uni-view", {staticClass: "padding-top-xs padding-bottom-sm"}, [i("v-uni-view", {staticClass: "grid col-4"}, [t._l(t.prop.thumb, (function (a, e) {
                return [i("v-uni-view", {
                    key: e + "_0", staticClass: "padding-xs wrap-box", on: {
                        click: function (i) {
                            arguments[0] = i = t.$handleEvent(i), t.preview(a, e)
                        }
                    }
                }, [i("v-uni-view", {staticClass: "square-wrap"}, [i("v-uni-view", {staticClass: "square-box"}, [i("v-uni-image", {
                    attrs: {
                        src: a,
                        "lazy-load": !0,
                        mode: "aspectFill"
                    }
                })], 1)], 1)], 1)]
            }))], 2)], 1) : t._e()], 1)
        }, o = []
    }, "35a5": function (t, a, i) {
        var e = i("24fb");
        a = e(!1), a.push([t.i, '@charset "UTF-8";\n/**\n * 这里是uni-app内置的常用样式变量\n *\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\n *\n */\n/**\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\n *\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\n */\n/* 颜色变量 */.text-through[data-v-2a1e4920]{text-decoration:line-through}.word-break-all[data-v-2a1e4920]{word-wrap:break-word;white-space:normal;word-break:break-all}.text-333[data-v-2a1e4920]{color:#333}.text-666[data-v-2a1e4920]{color:#666}.text-999[data-v-2a1e4920]{color:#999}.text-sm[data-v-2a1e4920]{font-size:%?24?%}.text-df[data-v-2a1e4920]{font-size:%?28?%}.text-lg[data-v-2a1e4920]{font-size:%?32?%}.text-xl[data-v-2a1e4920]{font-size:%?36?%}.text-xxl[data-v-2a1e4920]{font-size:%?42?%}.text-white[data-v-2a1e4920]{color:#fff}.text-price[data-v-2a1e4920]::before{content:"¥";font-size:80%;margin-right:%?4?%}.margin-left-sm[data-v-2a1e4920]{margin-left:%?20?%}.padding[data-v-2a1e4920]{padding:%?30?%}.bg-white[data-v-2a1e4920]{background-color:#fff}.flex[data-v-2a1e4920]{display:-webkit-box;display:-webkit-flex;display:flex}.justify-between[data-v-2a1e4920]{-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between}.align-center[data-v-2a1e4920]{-webkit-box-align:center;-webkit-align-items:center;align-items:center}.margin-top-sm[data-v-2a1e4920]{margin-top:%?20?%}.text-cut-2[data-v-2a1e4920]{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden}.margin-left[data-v-2a1e4920]{margin-left:%?30?%}.justify-center[data-v-2a1e4920]{-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center}.padding-lr[data-v-2a1e4920]{padding:0 %?30?%}.margin-left-xs[data-v-2a1e4920]{margin-left:%?10?%}.solid-bottom[data-v-2a1e4920]{position:relative}.solid-bottom[data-v-2a1e4920]::after{content:" ";width:200%;height:200%;position:absolute;top:0;left:0;-webkit-border-radius:inherit;border-radius:inherit;-webkit-transform:scale(.5);transform:scale(.5);-webkit-transform-origin:0 0;transform-origin:0 0;pointer-events:none;-webkit-box-sizing:border-box;box-sizing:border-box}.solid-bottom[data-v-2a1e4920]::after{border-bottom:%?1?% solid rgba(0,0,0,.1)}.padding-tb-sm[data-v-2a1e4920]{padding:%?20?% 0}.round[data-v-2a1e4920]{-webkit-border-radius:%?5000?%;border-radius:%?5000?%}.padding-bottom-sm[data-v-2a1e4920]{padding-bottom:%?20?%}.padding-top-sm[data-v-2a1e4920]{padding-top:%?20?%}.padding-top-xs[data-v-2a1e4920]{padding-top:%?10?%}.padding-xs[data-v-2a1e4920]{padding:%?10?%}.grid[data-v-2a1e4920]{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-flex-wrap:wrap;flex-wrap:wrap}.grid.col-4.grid-square > uni-view[data-v-2a1e4920]{padding-bottom:calc((100% - %?60?%)/4);height:0;width:calc((100% - %?60?%)/4)}.grid.col-2.grid-square > uni-view[data-v-2a1e4920]:nth-child(2n),\n.grid.col-3.grid-square > uni-view[data-v-2a1e4920]:nth-child(3n),\n.grid.col-4.grid-square > uni-view[data-v-2a1e4920]:nth-child(4n),\n.grid.col-5.grid-square > uni-view[data-v-2a1e4920]:nth-child(5n){margin-right:0}.grid.col-4 > uni-view[data-v-2a1e4920]{width:25%}\n/* 行为相关颜色 */\n/* 文字基本颜色 */\n/* 背景颜色 */\n/* 边框颜色 */\n/* 尺寸变量 */\n/* 文字尺寸 */\n/* 图片尺寸 */\n/* Border Radius */\n/* 水平间距 */\n/* 垂直间距 */\n/* 透明度 */\n/* 文章场景相关 */.wrap-box[data-v-2a1e4920]{background-color:#fff}.wrap-box .square-wrap[data-v-2a1e4920]{width:100%;height:0;padding-bottom:100%;-webkit-box-sizing:border-box;box-sizing:border-box;position:relative}.wrap-box .square-wrap .square-box[data-v-2a1e4920]{position:absolute;top:0;right:0;bottom:0;left:0;-webkit-border-radius:%?8?%;border-radius:%?8?%}.wrap-box .square-wrap .square-box uni-image[data-v-2a1e4920]{width:100%;height:100%;-webkit-border-radius:%?8?%;border-radius:%?8?%}.block-80[data-v-2a1e4920]{width:%?80?%;height:%?80?%;min-width:%?80?%;min-height:%?80?%;background-color:#eee;-webkit-border-radius:50%;border-radius:50%}.block-80 uni-image[data-v-2a1e4920]{width:100%;height:100%}.block-24[data-v-2a1e4920]{width:%?24?%;height:%?24?%;min-width:%?24?%;min-height:%?24?%}', ""]), t.exports = a
    }, "3aa8": function (t, a, i) {
        "use strict";
        var e = i("d0b3"), n = i.n(e);
        n.a
    }, "3ad7": function (t, a, i) {
        "use strict";
        i.r(a);
        var e = i("2379"), n = i("c601");
        for (var o in n) "default" !== o && function (t) {
            i.d(a, t, (function () {
                return n[t]
            }))
        }(o);
        i("ce80");
        var s, r = i("f0c5"),
            d = Object(r["a"])(n["default"], e["b"], e["c"], !1, null, "2a1e4920", null, !1, e["a"], s);
        a["default"] = d.exports
    }, "90c5": function (t, a, i) {
        "use strict";
        i.d(a, "b", (function () {
            return n
        })), i.d(a, "c", (function () {
            return o
        })), i.d(a, "a", (function () {
            return e
        }));
        var e = {fuEvaluate: i("3ad7").default}, n = function () {
            var t = this, a = t.$createElement, i = t._self._c || a;
            return i("v-uni-view", {staticClass: "page"}, [i("v-uni-view", {staticClass: "banners"}, [i("v-uni-swiper", {
                staticClass: "carousel",
                attrs: {"indicator-dots": !1, circular: !0},
                on: {
                    change: function (a) {
                        arguments[0] = a = t.$handleEvent(a), t.changeSwiper.apply(void 0, arguments)
                    }
                }
            }, [t._l(t.swipers, (function (t, a) {
                return [i("v-uni-swiper-item", [i("v-uni-image", {
                    attrs: {
                        src: t,
                        mode: "aspectFill",
                        "lazy-load": !0
                    }
                })], 1)]
            }))], 2), i("v-uni-view", {staticClass: "pagination"}, [t._v(t._s(t.curSwiper) + "/" + t._s(t.swipers.length))])], 1), i("v-uni-view", {staticClass: "prices"}, [i("v-uni-view", {staticClass: "box"}, [i("v-uni-view", {staticClass: "text-white"}, [i("v-uni-text", [i("v-uni-text", {staticClass: "text-price text-xl"}), i("v-uni-text", {staticClass: "text-xxl"}, [t._v(t._s(t.goodsInfo.price))])], 1), i("v-uni-text", {staticClass: "text-through margin-left-sm"}, [i("v-uni-text", {staticClass: "text-price text-lg"}), i("v-uni-text", {staticClass: "text-lg"}, [t._v(t._s(t.goodsInfo.market_price))])], 1)], 1)], 1)], 1), i("v-uni-view", {staticClass: "infos padding bg-white"}, [i("v-uni-view", {staticClass: "text-lg text-333 word-break-all text-cut-2"}, [t._v(t._s(t.goodsInfo.name))]), i("v-uni-view", {staticClass: "flex justify-between align-center margin-top-sm"}, [i("v-uni-text", {staticClass: "text-df text-999 word-break-all text-cut-2"}, [t._v(t._s(t.goodsInfo.description))])], 1), i("v-uni-view", {staticClass: "margin-top-sm text-sm text-999"}, [i("v-uni-text", [t._v("库存: " + t._s(t.goodsInfo.totalStock))]), i("v-uni-text", {staticClass: "margin-left"}, [t._v("销量: " + t._s(t.goodsInfo.sales_sum))])], 1)], 1), i("v-uni-view", {staticClass: "evaluates margin-top-sm radius-20 bg-white padding-lr"}, [i("v-uni-view", {staticClass: "title flex justify-between align-center"}, [i("v-uni-text", [i("v-uni-text", {staticClass: "text-lg text-333"}, [t._v("商品评价")]), i("v-uni-text", {staticClass: "text-333 text-df margin-left-xs"}, [t._v("(" + t._s(t.comment_total) + ")")])], 1), i("v-uni-text", {
                on: {
                    click: function (a) {
                        arguments[0] = a = t.$handleEvent(a), t.navToComment.apply(void 0, arguments)
                    }
                }
            }, [i("v-uni-text", {staticClass: "text-sm text-999"}, [t._v("查看全部")]), i("v-uni-text", {
                staticClass: "cuIcon-right text-999 margin-left-xs",
                staticStyle: {"font-size": "24rpx"}
            })], 1)], 1), t.comment.length > 0 ? i("v-uni-view", {staticClass: "content"}, [t._l(t.comment, (function (t, a) {
                return [i("fu-evaluate", {key: a + "_0", attrs: {prop: t}})]
            }))], 2) : t._e()], 1), i("v-uni-view", {
                staticClass: "details radius-20 bg-white margin-top-sm",
                attrs: {id: "details"}
            }, [i("v-uni-view", {staticClass: "title"}, [i("v-uni-view", {staticClass: "icon icon1"}), i("v-uni-view", {staticClass: "margin-lr"}, [t._v("商品详情")]), i("v-uni-view", {staticClass: "icon icon2"})], 1), i("v-uni-view", {staticClass: "padding-lr padding-bottom word-break-all"}, [i("v-uni-view", {domProps: {innerHTML: t._s(t.body)}})], 1)], 1), i("v-uni-view", {staticClass: "tools"}, [i("v-uni-view", {staticClass: "toolbar"}, [i("v-uni-view", {
                staticClass: "toolbar-left",
                on: {
                    click: function (a) {
                        arguments[0] = a = t.$handleEvent(a), t.downloadAPK.apply(void 0, arguments)
                    }
                }
            }, [t._v("下载APP")]), i("v-uni-view", {
                staticClass: "toolbar-right", on: {
                    click: function (a) {
                        arguments[0] = a = t.$handleEvent(a), t.openAPK.apply(void 0, arguments)
                    }
                }
            }, [t._v("打开APP")])], 1)], 1)], 1)
        }, o = []
    }, a758: function (t, a, i) {
        "use strict";
        i.r(a);
        var e = i("1c2c"), n = i.n(e);
        for (var o in e) "default" !== o && function (t) {
            i.d(a, t, (function () {
                return e[t]
            }))
        }(o);
        a["default"] = n.a
    }, bac6: function (t, a, i) {
        var e = i("35a5");
        "string" === typeof e && (e = [[t.i, e, ""]]), e.locals && (t.exports = e.locals);
        var n = i("4f06").default;
        n("157e7bf3", e, !0, {sourceMap: !1, shadowMode: !1})
    }, c601: function (t, a, i) {
        "use strict";
        i.r(a);
        var e = i("0962"), n = i.n(e);
        for (var o in e) "default" !== o && function (t) {
            i.d(a, t, (function () {
                return e[t]
            }))
        }(o);
        a["default"] = n.a
    }, ce80: function (t, a, i) {
        "use strict";
        var e = i("bac6"), n = i.n(e);
        n.a
    }, d0b3: function (t, a, i) {
        var e = i("02f3");
        "string" === typeof e && (e = [[t.i, e, ""]]), e.locals && (t.exports = e.locals);
        var n = i("4f06").default;
        n("9a7a1f3a", e, !0, {sourceMap: !1, shadowMode: !1})
    }
}]);