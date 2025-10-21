import Vue from "vue";
import TagCounterWidget from "./views/TagCounterWidget.vue";

import { translate, translatePlural } from "@nextcloud/l10n";

Vue.prototype.t = translate;
Vue.prototype.n = translatePlural;
Vue.prototype.OC = window.OC;
Vue.prototype.OCA = window.OCA;

document.addEventListener("DOMContentLoaded", () => {
  OCA.Dashboard.register(
    "documentcontroltags-tag-counter-widget",
    (el, { widget }) => {
      const View = Vue.extend(TagCounterWidget);
      new View({
        propsData: { title: widget.title },
      }).$mount(el);
    }
  );
});
