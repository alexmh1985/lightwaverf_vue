<template>
  <div id="app">
    <devices/>
  </div>
</template>

<script>
import devices from './components/devices.vue'

export default {
  name: 'App',
  components: {
    devices
  }
}
</script>

<style>
#app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  color: #2c3e50;
  margin-top: 60px;
}
</style>
