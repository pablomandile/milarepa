<!-- InputLabel.vue -->
<template>
    <label :for="hfor" class="block text-sm font-medium text-gray-700">
      {{ value }}
      <span v-if="required" class="text-red-500 text-xs align-top">*</span>
    </label>
  </template>
  
  <script>
  export default {
    props: {
      hfor: String,
      value: String,
      required: Boolean,
    },
  };
  </script>