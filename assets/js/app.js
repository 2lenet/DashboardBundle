import "../css/app.scss";

import 'gridstack/dist/gridstack.min.css';
import { GridStack } from 'gridstack';
import html2pdf from 'html2pdf.js';
import Chart from 'chart.js/auto';

global.Chart = Chart;

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
    grid.disable();

    // Update grid when user adds a new widget
    initializeAddedHandler(grid);

    // Button to add a widget
    initializeAddWidget(grid);

    // Save changes when a widget is moved or resized
    initializeChangeHandler(grid);

    // Allow editing widget titles
    initializeTitleEdition(grid);

    // Allow to export widget as a PDF
    initializeExportWidget();

    /**
     * Load widgets
     */
    let container = document.querySelector(".grid-stack");
    let items = JSON.parse(container.dataset.widgets);

    for (let item of items) {
        grid.addWidget(createWidgetElement(item));
    }

    let loading = false;
    let total = 0;

    for (let widget of grid.getGridItems()) {
        if (widget.dataset.ajax) {

            if (!loading) {
                toggleSpin();
                loading = true;
            }

            let url = Routing.generate("render_widget", {id: widget.getAttribute('gs-id')});

            fetch(url)
                .then((response) => {
                    response.text().then((html) => {

                        if (!response.ok) {
                            let htmlError = "<div>Error loading wigdet</div>";
                            document.getElementById("widget_body_" + widget.getAttribute("gs-id")).innerHTML = htmlError;
                        } else {
                            let loadedWidget = createWidgetElement(html);

                            grid.update(widget, {
                                content: loadedWidget.children[0].innerHTML
                            });

                            initializeButtons({
                                id: widget.id.replace("widget_", "")
                            });
                            enableScripts(widget);
                        }
                        total++;
                        if (total === grid.getGridItems().length) {
                            if (loading) {
                                toggleSpin();
                            }
                            grid.enable();
                        }
                    });
                });
        } else {
            enableScripts(widget);
            total++;
            if (total === grid.getGridItems().length) {
                if (loading) {
                    toggleSpin();
                }
                grid.enable();
            }
        }
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
    const options = document.querySelectorAll(".add-widget");
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
    const width = window.innerWidth;
    window.addEventListener('resize', function () {
        if (window.innerWidth !== width) {
            location.reload();
        }
    });

    grid.on('resize', function (event, widget) {
        updateWidget(widget);
    });

    grid.on('drag', function (event, widget) {
        updateWidget(widget);
    });
}

function initializeButtons(widget) {
    // Handle widget deletion
    document.querySelector(`#widget_close_${ widget.id }`)
        .addEventListener("click", () => {
            toggleSpin();
            let url = Routing.generate("remove_widget", {id: widget.id});
            fetch(url)
                .then(() => {
                    location.reload();
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

function initializeAddedHandler(grid) {
    grid.on("added", function (event, widgets) {
        for (let widget of widgets) {
            initializeButtons(widget);
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

function initializeExportWidget() {
    document.addEventListener('click', (event) => {
        if (event.target.classList.contains('lle-dashboard-widget-export')) {
            const widget = document.getElementById(event.target.dataset.export);
            const widgetTitle = event.target.dataset.exportName;
            let orientation = event.target.dataset.exportOrientation ?? 'portrait';
            let format = event.target.dataset.exportFormat ?? 'a4';

            html2pdf()
                .set({
                    margin: 10,
                    filename: `${ widgetTitle }.pdf`,
                    image: {
                        type: 'jpg',
                        quality: 1
                    },
                    html2canvas: {
                        scale: 2
                    },
                    jsPDF: {
                        orientation,
                        format
                    }
                })
                .from(widget)
                .save()
            ;
        }
    });
}
