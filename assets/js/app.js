if (typeof window.Vue !== "undefined") {
  const vm = new Vue({
    el: document.querySelector("#app"),
    components: {
      app: window.httpVueLoader("/wp-content/plugins/route-map/assets/js/components/App.vue"),
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
}
