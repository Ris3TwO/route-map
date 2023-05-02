<template>
  <div>
    <transition name="fade" tag="div">
      <h2 v-if="title !== ''" class="route-name">{{ title }}</h2>
    </transition>
    <div v-if="routes.length !== 0" class="route-items">
      <transition-group name="fade">
        <div
          class="route-item"
          v-for="route in routes"
          :key="route.name.toLowerCase()"
        >
          <div class="route-item-name">{{ route.name }}</div>
          <div class="route-item-description" v-html="route.description"></div>
          <div class="map-flex map-jc-center">
            <a :href="route.link" target="_blank" class="route-item-link">
              Conocer más
            </a>
          </div>
        </div>
      </transition-group>
    </div>
  </div>
</template>

<script>
module.exports = {
  name: "Routes",
  props: {
    routes: {
      type: Array,
      default: () => [],
      required: true,
    },
    title: {
      type: String,
      default: "",
      required: true,
    },
  },
};
</script>
