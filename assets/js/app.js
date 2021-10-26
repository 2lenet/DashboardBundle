import "../css/app.scss";

import 'gridstack/dist/gridstack.min.css';
import {GridStack} from 'gridstack';
import 'gridstack/dist/h5/gridstack-dd-native';

let onLoad = (callback) => {
    if (document.readyState !== "loading") {
        callback();
    } else {
        document.addEventListener("DOMContentLoaded", callback);
    }
}

onLoad(() => {
    /*
        var widgetsRendered = 0;
        {% for widget in widgets %}
        {% if widget.support() %}
        {% if widget.supportsAjax() %}

        {# Dynamically load the widget. #}
        $(function () {


        var grid = $('.grid-stack').data('gridstack');

        var emptyWidget = $("{{ include('@LleDashboard/widget/empty_widget.html.twig', { widget: widget}) | escape("js") }}");
        grid.addWidget(emptyWidget);

        $.ajax({
        url: Routing.generate('render_widget', {id: {{ widget.id }}}),
        success: function (response) {

        // Update the grid
        grid.removeWidget(emptyWidget, true);
        grid.addWidget(response);

        addWidgetListeners({{ widget.id }});
    },
        complete: function (response) {
        // When all widgets are rendered, enable ability for grid to change
        // do this in 'complete' so if some widgets have an error, still allow grid editing
        widgetsRendered++;
        if (widgetsRendered == {{ widgets|length }}) {
        enableGridChanges();
    }
    }
    });
    });
        {% else %}
        {# Directly load the widget #}
        $(function () {
        // Update the grid
        var grid = $('.grid-stack').data('gridstack');
        var widget = $("{{ widget.render() | escape("js") }}");
        grid.addWidget(widget);

        addWidgetListeners({{ widget.id }});
        // When all widgets are rendered, enable ability for grid to change
        // do this in 'complete' so if some widgets have an error, still allow grid editing
        widgetsRendered++;
        if (widgetsRendered == {{ widgets|length }}) {
        enableGridChanges();
    }
    });
        {% endif %}
        {% endif %}
        {% endfor %}
*/
    let options = {
        animate: true,
        float: true,
        // so we can only drag by clicking the title, so it won't drag if we select inside
        handleClass: "card-header",
        alwaysShowResizeHandle: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
        resizable: {
            // Don't put handles on the sizes so the user can still interact with scroll bars
            handles: "se, sw, ne, nw"
        }
    }

    /**
     * Initialize dashboard
     */
    let grid = GridStack.init(options);

    // Update grid when user adds a new widget
    initializeAddedHandler(grid);

    // Button to add a widget
    initializeAddWidget(grid);

    // Save changes when a widget is moved or resized
    initializeChangeHandler(grid);

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

    /*
    grid.disable();*/
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
    Array.from(widget.querySelectorAll("script")).forEach( oldScript => {
        const newScript = document.createElement("script");
        Array.from(oldScript.attributes)
            .forEach( attr => newScript.setAttribute(attr.name, attr.value) );
        newScript.appendChild(document.createTextNode(oldScript.innerHTML));
        oldScript.parentNode.replaceChild(newScript, oldScript);
    });
}

// Edit the title of a widget.
// function editTitle(title) {
//
//     // SCENE
//     var container = title.closest('div');
//     var newContainer = container.clone(true);
//
//     // FORM CONTROL
//     var input = $('<input></input>')
//         .attr('type', 'text')
//         .attr('value', title.text().trim())
//         .addClass('pull-left form-control')
//         .css('width', '15%')
//         .css('min-width', '150px');
//     var form = $('<form></form>').append(input).attr('action', '#');
//
//     var id = $(title).closest('.grid-stack-item').data('widget-id');
//
//     // user presses enter
//     form.submit(function () {
//         $(newContainer).find('span#widget_title').text(input.val()).append("&nbsp;");
//         $.ajax(Routing.generate('update_title', {id: id, title: input.val()}));
//         form.replaceWith(newContainer);
//     })
//
//     // user focuses out
//     input.blur(function () {
//         $(newContainer).find('span#widget_title').text(input.val()).append("&nbsp;");
//         $.ajax(Routing.generate('update_title', {id: id, title: input.val()}));
//         form.replaceWith(newContainer);
//     })
//
//     // show input and focus
//     container.replaceWith(form);
//     input.focus()[0].setSelectionRange(99999, 99999);
// }

function toggleSpin() {
    document.querySelector("#gs-spin").classList.toggle("fa-circle-notch");
}

function toggleConfigPanel(id) {
    document.querySelector(`#widget_${id} .card-body`).classList.toggle("d-none");
    document.querySelector(`#form_${id}`).classList.toggle("d-none");
}
//
// function enableGridChanges() {
//     grid = $('.grid-stack').data('gridstack');
//     grid.enable();
// }

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
    grid.on("change", function (event, widgets) {

        // Create EventHandler for widget update
        for (let widget of widgets) {

            let url = Routing.generate("update_widget", {
                id: widget.id,
                x: widget.x,
                y: widget.y,
                width: widget.w,
                height: widget.h,
            });

            fetch(url);
        }

    });
}

function initializeAddedHandler(grid) {
    grid.on("added", function (event, widgets) {
        for (let widget of widgets) {

            // Handle widget deletion
            document.querySelector(`#widget_close_${widget.id}`)
                .addEventListener("click", () => {
                    toggleSpin();
                    let url = Routing.generate("remove_widget", { id : widget.id });
                    fetch(url)
                        .then(() => {
                            grid.removeWidget(widget.el);
                        })
                        .finally(() => {
                            toggleSpin();
                        });
                });

            // Handle widget config panel
            document.querySelector(`#config_${ widget.id }`)
                .addEventListener("click", () => {
                    toggleConfigPanel(widget.id);
                });

            // Handle widget config form
            /*
            let editor = window[`editor${widget.id}`];
            editor.addEventListener("change", function () {

                // Save config in a field with the correct data because Symfony is screwing up some field values
                document.querySelector(`#form_json_${widget.id}`)
                    .value = JSON.stringify(editor.getValue());
            });*/
        }
    });
}
