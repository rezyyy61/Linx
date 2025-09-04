import type { Composer } from "vue-i18n";

declare module "@vue/runtime-core" {
  interface ComponentCustomProperties {
    $i18n: Composer;
    $t: Composer["t"];
    $d: Composer["d"];
    $n: Composer["n"];
    $tm: Composer["tm"];
  }
}

export {};
