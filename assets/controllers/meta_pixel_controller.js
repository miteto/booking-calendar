import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        this.onLoad = this.onLoad.bind(this);
        document.addEventListener("turbo:load", this.onLoad);
    }

    disconnect() {
        document.removeEventListener("turbo:load", this.onLoad);
    }

    onLoad() {
        if (typeof window.fbq !== "function") return;

        const url = window.location.href;
        if (window.__lastMetaPV === url) return; // dedup, if load happens twice
        window.__lastMetaPV = url;

        window.fbq("track", "PageView");
    }
}
