import "../css/app.scss";

import 'gridstack/dist/gridstack.min.css';
import { GridStack } from 'gridstack';

let onLoad = (callback) => {
    if (document.readyState !== "loading") {
        callback();
    } else {
        document.addEventListener("DOMContentLoaded", callback);
    }
}

onLoad(() => {
    let options = {
        animate: true,
        float: true,
        // so we can only drag by clicking the title, so it won't drag if we select inside
        handleClass: "card-header",
        alwaysShowResizeHandle: 'mobile',
        resizable: {
            // Don't put handles on the sizes so the user can still interact with scroll bars
            handles: "se, sw, ne, nw"
        }
    }

    /**
     * Initialize dashboard
     */
    const grid = GridStack.init(options);

    // Update grid when user adds a new widget
    initializeAddedHandler(grid);

    // Button to add a widget
    initializeAddWidget(grid);

    // Save changes when a widget is moved or resized
    initializeChangeHandler(grid);

    // Allow editing widget titles
    initializeTitleEdition(grid);

    /**
     * Load widgets
     */
    let container = document.querySelector(".grid-stack");
    let items = JSON.parse(container.dataset.widgets);

    for (let item of items) {
        grid.addWidget(createWidgetElement(item));
    }

    for (let widget of grid.getGridItems()) {
        enableScripts(widget);
    }

    /**
     * Clear useless stuff
     */
    container.removeAttribute("data-widgets");
});

/**
 * initialize DOM widget element
 */
function createWidgetElement(html) {
    let el = document.createElement("template");
    el.innerHTML = html.trim();

    return el.content.firstChild;
}

/**
 * @see https://stackoverflow.com/a/47614491
 *
 * enable scripts in widgets
 */
function enableScripts(widget) {
    Array.from(widget.querySelectorAll("script")).forEach(oldScript => {
        const newScript = document.createElement("script");
        Array.from(oldScript.attributes)
            .forEach(attr => newScript.setAttribute(attr.name, attr.value));
        newScript.appendChild(document.createTextNode(oldScript.innerHTML));
        oldScript.parentNode.replaceChild(newScript, oldScript);
    });
}

function toggleSpin() {
    document.querySelector("#gs-spin").classList.toggle("fa-circle-notch");
}

function toggleConfigPanel(id) {
    document.querySelector(`#widget_${ id } .card-body`).classList.toggle("d-none");
    document.querySelector(`#form_${ id }`).classList.toggle("d-none");
}

function initializeAddWidget(grid) {
    let options = document.querySelectorAll(".add-widget");
    for (let option of options) {
        option.addEventListener("click", (e) => {
            toggleSpin();
            let type = e.target.dataset.type;
            let url = Routing.generate("add_widget", {type: type});

            fetch(url)
                .then((response) => {
                    response.text().then((html) => {
                        let widget = createWidgetElement(html);
                        grid.addWidget(widget);
                        enableScripts(widget);
                    });
                })
                .finally(() => {
                    toggleSpin();
                });
        });
    }
}

function initializeChangeHandler(grid) {
    window.addEventListener('resize', function () {
        location.reload();
    });

    grid.on('resize', function (event, widget) {
        updateWidget(widget);
    });

    grid.on('drag', function (event, widget) {
        updateWidget(widget);
    });
}

function initializeAddedHandler(grid) {
    grid.on("added", function (event, widgets) {
        for (let widget of widgets) {

            // Handle widget deletion
            document.querySelector(`#widget_close_${ widget.id }`)
                .addEventListener("click", () => {
                    toggleSpin();
                    let url = Routing.generate("remove_widget", {id: widget.id});
                    fetch(url)
                        .then(() => {
                            grid.removeWidget(widget.el);
                        })
                        .finally(() => {
                            toggleSpin();
                        });
                });

            // Handle widget config panel
            let configBtn = document.querySelector(`#config_${ widget.id }`);

            if (configBtn) {
                configBtn.addEventListener("click", () => {
                    toggleConfigPanel(widget.id);
                });
            }
        }
    });
}

function initializeTitleEdition(grid) {
    document.addEventListener("dblclick", e => {

        let input = e.target;

        if (!input.classList.contains("lle-dashboard-input-title")) {
            return;
        }

        // user double clicks on the input, enable the input
        enableTitleInput(grid, input);
    });

    document.addEventListener("click", e => {

        // user clicks out of the input, disable the input
        if (!e.target.classList.contains("lle-dashboard-input-title")) {
            document.querySelectorAll(".lle-dashboard-input-title").forEach(i => {

                if (!i.hasAttribute("readonly")) {
                    saveTitleInput(grid, i);
                }
            });
        }

        if (e.target.classList.contains("lle-dashboard-widget-edit")) {
            let input = document.querySelector(e.target.dataset.target);

            enableTitleInput(grid, input);
        }
    });

    document.addEventListener("keypress", e => {
        let input = document.activeElement;

        if (input && input.classList.contains("lle-dashboard-input-title--active") && e.key === "Enter") {
            saveTitleInput(grid, input);
        }
    });
}

function enableTitleInput(grid, input) {
    grid.enableMove(false);

    input.focus();
    input.removeAttribute("readonly");
    input.classList.add("lle-dashboard-input-title--active");
    input.setSelectionRange(0, 99999);
}

function updateWidget(widget) {
    const url = Routing.generate('update_widget', {
        id: widget.getAttribute('gs-id'),
        x: widget.getAttribute('gs-x'),
        y: widget.getAttribute('gs-y'),
        width: widget.getAttribute('gs-w'),
        height: widget.getAttribute('gs-h'),
    });

    fetch(url);
}

function saveTitleInput(grid, input) {
    // save edited value
    toggleSpin();

    let url = Routing.generate("update_title", {
        id: input.dataset.widgetid,
        title: input.value
    });

    fetch(url)
        .catch(() => {
            input.value = "#ERROR";
        })
        .finally(() => {
            toggleSpin();
        });

    input.setAttribute("readonly", "readonly");
    input.classList.remove("lle-dashboard-input-title--active");
    input.setSelectionRange(0, 0); // it's still seletected on click, unselect it

    grid.enableMove(true);
}
