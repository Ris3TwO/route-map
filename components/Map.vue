<template>
  <div class="departments">
    <div
      v-for="department in departmentList"
      :class="`department department-${department.id} ${
        department.hasData ? 'department-available department-clickable' : ''
      } ${
        keepData && activeDepartment === department.name
          ? 'department-active'
          : ''
      }`"
      @click="department.hasData ? getRoutes(department.name) : false"
    >
      <img :src="department.image" alt="test" />
    </div>
  </div>
</template>

<script>
module.exports = {
  name: "MapColombia",
  props: {
    routes: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      departments: [],
      departmentList: [],
      activeDepartment: "",
      keepData: false,
    };
  },
  watch: {
    routes: {
      handler() {
        this.setdepartments();
      },
      deep: true,
    },
  },
  mounted() {
    this.getMapDepartments();
  },
  methods: {
    getMapDepartments() {
      let url = "/wp-content/plugins/route-map/data/colombia.json";
      fetch(url)
        .then((response) => {
          return response.json();
        })
        .then((data) => {
          this.departmentList = data;
        });
    },
    setdepartments() {
      const departmentList = {
        andina: ["Cundinamarca", "Tolima"],
        central: ["Caldas", "Risaralda", "Tolima"],
        inirida: ["Guainía", "Cundinamarca"],
        caribe: ["Atlántico", "Magdalena", "Guajira"],
      };

      if (this.routes.length === 0) return;

      this.routes.forEach((route) => {
        // console.log(route);
        const name = route.title.rendered.split("Ruta ")[1].toLowerCase();

        const departments = departmentList[name] || null;

        if (departments) {
          departments.forEach((department) => {
            const hasDepartment = this.departments.findIndex(
              (r) => r.department === department
            );

            if (hasDepartment !== -1) {
              const info = {
                name: route.title.rendered,
                description: route.excerpt.rendered,
                link: route.link,
              };
              this.departments[hasDepartment].routes.push(info);
              return;
            }
            const data = {
              department,
              routes: [
                {
                  name: route.title.rendered,
                  description: route.excerpt.rendered,
                  link: route.link,
                },
              ],
            };
            this.departments.push(data);
          });
        }
      });
    },
    getRoutes(department) {
      if (this.keepData && this.activeDepartment === department) {
        this.resetRoutes();
        this.keepData = false;
        return;
      }
      let vm = this;
      const res = this.departments.find((r) => r.department === department);
      if (res) {
        this.keepData = true;
        this.activeDepartment = res.department;
        vm.$emit("route-list", {
          routes: res.routes,
          department: res.department,
        });
        return; // return to avoid the keepData
      }
      this.keepData = !this.keepData;
    },
    resetRoutes() {
      console.log("reset");
      this.$emit("reset-routes");
    },
  },
};
</script>
