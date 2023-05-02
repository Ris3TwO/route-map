<template>
  <div class="departments">
    <div
      v-for="department in departmentWithRoutes"
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
      departmentsId: [],
      departmentList: [],
      departmentWithRoutes: [],
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
      let url = "/wp-content/plugins/route-map/assets/data/colombia.json";
      fetch(url)
        .then((response) => {
          return response.json();
        })
        .then((data) => {
          this.departmentList = data;
        });
    },
    setdepartments() {
      if (this.routes.length === 0) {
        this.departmentWithRoutes = this.departmentList;
        return;
      }

      this.routes.forEach((route) => {

        this.departmentsId = [
          ...new Set([...this.departmentsId, ...route.departments.split(", ")]),
        ];
        // route.departments.split(", "));
        const departments = route.departmentNames.split(", ");

        if (departments) {
          departments.forEach((department) => {
            const hasDepartment = this.departments.findIndex(
              (r) => r.department === department
            );

            if (hasDepartment !== -1) {
              const info = {
                name: route.title,
                description: route.description,
                link: route.link,
              };
              this.departments[hasDepartment].routes.push(info);
              return;
            }
            const data = {
              department,
              routes: [
                {
                  name: route.title,
                  description: route.description,
                  link: route.link,
                },
              ],
            };
            this.departments.push(data);
          });
        }
      });

      this.departmentList.forEach((department) => {
        const hasDepartment = this.departmentsId.findIndex(
          (r) => parseInt(r, 10) === department.id
        );

        if (hasDepartment !== -1) {
          department.hasData = true;
          department.routes = this.departments[hasDepartment].routes;
        }
        this.departmentWithRoutes.push(department);
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
