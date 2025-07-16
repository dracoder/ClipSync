<template>
  <StudioAppLayout>
    <div v-if="isFree">
      <FreeComponent />
    </div>
    <div v-else>
      <PremiumComponent />
    </div>
  </StudioAppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import PremiumComponent from '@/components/studio/Premium.vue';
import FreeComponent from '@/components/studio/Free.vue';
import StudioAppLayout from '@/layouts/studio/AppLayout.vue';

const route = useRoute();
const isFree = route.query.free === true || route.query.free === 'true' || route.query.free == 1;

watch(
  () => [route.query.free, route.params.room],
  () => {
    componentKey.value++;
  }
);

</script>