<template>
  <b-container fluid>
    <div v-if="rooms">
      <b-row>
        <b-col cols="6 " v-for="room, room_name in rooms" :key="room_name">
          <b-card :header="room_name" class="mb-4" header-bg-variant="secondary" header-text-variant="white">
            <b-row>
              <b-col cols="2" v-for="items, item_name in room" :key="item_name" class="mb-2">
                <b-card :header="item_name" border-variant="primary" header-bg-variant="light">
                  <div v-for="feature, feature_name in items" :key="feature_name">
                    <b-btn v-if="feature.switch" @click="controlDevice(feature.switch.id, 'switch', feature.switch.status), feature.switch.status = feature.switch.status === 1 ? 0 : 1" class="mb-2" :variant="feature.switch.status === 1 ? 'secondary' : 'success'" size="sm">{{feature_name}}</b-btn>
                    <b-form-input v-if="feature.dimLevel && feature.switch && feature.switch.status === 1" type="range" @change="controlDevice(feature.dimLevel.id, 'dim', feature.dimLevel.status)" v-model="feature.dimLevel.status" min="0" max="100"></b-form-input>
                  </div>
                </b-card>
              </b-col>
            </b-row>
          </b-card>
        </b-col>
      </b-row>
    </div>
    <div v-else>
      No response from api
    </div>
  </b-container>
</template>

<script>
import axios from 'axios'
export default {
  name: 'devices',
  data () {
      return {
          rooms: false
      }
  },
  methods: {
      getDevices () {
        axios.get('api.php/?request=getdevices')
        .then((response) => {
          if(response.data.result) {
            this.rooms = response.data.rooms
          } else {
            console.log(response);
          }
        })
        .catch((error) => {
          console.log(error);
        })
      },
      controlDevice (feature, type, status) {
        var value = status
        if(type === 'switch') {
          value = status === 1 ? 0 : 1;
        }
        axios.get('api.php/?request=control', {
          params: {
            feature: feature,
            status: value
          }
        }).then((response) => {
          console.log(response)
        }).catch((e) => {
          console.log(e)
        })
      },
  },
  mounted() {
    this.getDevices()
  }
}
</script>

<style scoped>
</style>
