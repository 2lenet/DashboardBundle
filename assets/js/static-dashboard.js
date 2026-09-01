/**
 * Static dashboard: loads the widgets by ajax, and handles the tabs when the application's
 * provider declares some (see StaticTabProviderInterface).
 */
const TAB_HASH_PREFIX = "tab-";

let onLoad = (callback) => {
    if (document.readyState !== "loading") {
        callback();
    } else {
        document.addEventListener("DOMContentLoaded", callback);
    }
}

let loadWidget = (container) => {
    if (container.dataset.widgetLoaded === "1") {
        return;
    }
    container.dataset.widgetLoaded = "1";

    const url = Routing.generate("render_static_widget", { staticIndex: container.dataset.widgetIndex });

    fetch(url)
        .then((response) => response.text())
        .then((html) => {
            container.innerHTML = html;

            // Scripts injected through innerHTML are inert: recreate them so they run
            for (const oldScript of container.querySelectorAll("script")) {
                const newScript = document.createElement("script");
                for (const attribute of oldScript.attributes) {
                    newScript.setAttribute(attribute.name, attribute.value);
                }
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                oldScript.parentNode.replaceChild(newScript, oldScript);
            }
        });
}

let loadWidgets = (root) => {
    root.querySelectorAll(".static-widget-container").forEach(loadWidget);
}

/**
 * Widgets of a hidden tab are only fetched when the tab is displayed for the first time:
 * a widget rendered in a hidden pane cannot compute its own width (tables, charts).
 */
let activateTab = (tabKey, updateHash) => {
    document.querySelectorAll("[data-static-tab]").forEach((tab) => {
        const isActive = tab.dataset.staticTab === tabKey;
        tab.classList.toggle("active", isActive);
        tab.setAttribute("aria-selected", isActive ? "true" : "false");
    });

    document.querySelectorAll("[data-static-tab-pane]").forEach((pane) => {
        const isActive = pane.dataset.staticTabPane === tabKey;
        pane.classList.toggle("active", isActive);
        if (isActive) {
            loadWidgets(pane);
        }
    });

    if (updateHash) {
        history.replaceState(null, "", "#" + TAB_HASH_PREFIX + tabKey);
    }
}

let requestedTabKey = () => {
    const hash = window.location.hash;
    if (hash.indexOf("#" + TAB_HASH_PREFIX) !== 0) {
        return null;
    }

    const tabKey = hash.substring(TAB_HASH_PREFIX.length + 1);

    return document.querySelector(`[data-static-tab-pane="${CSS.escape(tabKey)}"]`) ? tabKey : null;
}

onLoad(() => {
    const tabs = document.querySelectorAll("[data-static-tab]");

    // Dashboard without tabs: every widget is displayed at once
    if (tabs.length === 0) {
        loadWidgets(document);

        return;
    }

    tabs.forEach((tab) => {
        tab.addEventListener("click", () => activateTab(tab.dataset.staticTab, true));
    });

    activateTab(requestedTabKey() || tabs[0].dataset.staticTab, false);
});
