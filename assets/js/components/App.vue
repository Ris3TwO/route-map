<template>
  <div class="map-section">
    <div class="map-container">
      <div class="map-row">
        <div class="map-col-12 map-lg-col-5 map-flex map-column">
          <div class="map-description">
            En <span>Aweima</span> pajareamos todos, por eso, contamos con rutas
            diseñadas para disfrutar y aprovechar al maximo una experiencia real
            de naturaleza entre hábitats ricos en fauna y flora, con guías
            expertos que propiciarán las condiciones necesaras para chequear el
            mayor número de especies y garantizar la seguridad de nuestra
            bandada.
          </div>
          <routes
            class="mt-5"
            :title="activeDepartment"
            :routes="routes"
          ></routes>
        </div>
        <div
          class="map-col-12 map-lg-col-7 map-flex map-jc-center map-ai-start"
        >
          <map-colombia
            :routes="pages"
            @route-list="getRoutes"
            @reset-routes="resetRoutes"
          ></map-colombia>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
module.exports = {
  name: "App",
  data() {
    return {
      pages: [],
      routes: [],
      activeDepartment: "",
    };
  },
  components: {
    "map-colombia": window.httpVueLoader(
      "/wp-content/plugins/route-map/assets/js/components/Map.vue"
    ),
    routes: window.httpVueLoader(
      "/wp-content/plugins/route-map/assets/js/components/Routes.vue"
    ),
  },
  filters: {
    strippedContent: function (string) {
      return string.replace(/<\/?[^>]+>/gi, " ");
    },
  },
  mounted() {
    this.getPages();
  },
  methods: {
    getRoutes({ routes, department }) {
      this.activeDepartment = department;
      this.routes = routes;
      console.log("Listados de rutas", routes);
    },
    resetRoutes() {
      this.activeDepartment = "";
      this.routes = [];
    },
    getPages() {
      let url = "/wp-json/route-map/v1/routes";
      fetch(url)
        .then((response) => {
          return response.json();
        })
        .then((data) => {
          this.pages = data;
        });
    },
  },
};
</script>
