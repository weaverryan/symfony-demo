(self["webpackChunk"] = self["webpackChunk"] || []).push([["login"],{

/***/ "./assets/js/login.js":
/*!****************************!*\
  !*** ./assets/js/login.js ***!
  \****************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
$(function () {
  var usernameEl = $('#username');
  var passwordEl = $('#password'); // in a real application, the user/password should never be hardcoded
  // but for the demo application it's very convenient to do so

  if (!usernameEl.val() || 'jane_admin' === usernameEl.val()) {
    usernameEl.val('jane_admin');
    passwordEl.val('kitten');
  }
});

/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ "use strict";
/******/ 
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ __webpack_require__.O(0, ["vendors-node_modules_jquery_dist_jquery_js"], () => (__webpack_exec__("./assets/js/login.js")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);