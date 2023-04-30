if (typeof window.Vue !== "undefined") {
  const vm = new Vue({
    el: document.querySelector("#app"),
    components: {
      app: window.httpVueLoader("/wp-content/plugins/route-map/App.vue"),
    },
    data: {
      pages: [],
    },
    template: `
    <app></app>
    `,
    mounted: function () {
      // console.log("Hello Vue!");
    },
  });
  const vm2 = new Vue({
    el: document.querySelector("#admin"),
    components: {
      admin: window.httpVueLoader("/wp-content/plugins/route-map/components/Admin.vue"),
    },
    data: {
      pages: [],
    },
    template: `
    <admin></admin>
    `,
    mounted: function () {
      // console.log("Hello Vue!");
    },
  });
}
