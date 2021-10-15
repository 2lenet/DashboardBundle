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
    /**
     * Initialisation
     */
    let grid = GridStack.init({
        width: 12,
        animate: true,
        float: true,
        // so we can only drag by clicking the title, so it won't drag if we select inside
        handleClass: "card-header",
        alwaysShowResizeHandle: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
        resizable: {
            // Don't put handles on the sizes so the user can still interact with scroll bars
            handles: "se, sw, ne, nw"
        }
    });

    grid.on("added", function (event, widgets) {
        for (let widget of widgets) {

            // Create EventHandler for widget deletion
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
        }
    });

    // save changes
    grid.on('change', function (event, widgets) {

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

    /**
     * Chargement des widgets
     */
    let items = JSON.parse(document.querySelector(".grid-stack").dataset.widgets);

    grid.load(items);

    // add widget button
    document
        .querySelector(".add-widget")
        .addEventListener("click", (e) => {
            toggleSpin();
            let type = e.target.dataset.type;
            let url = Routing.generate('add_widget', {type: type});

            fetch(url)
                .then((response) => {
                    response.json().then((widget) => {
                        grid.addWidget(widget);
                    });
                })
                .finally(() => {
                    toggleSpin();
                });
    })

    /*
    grid.disable();*/
});

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

// function toggleConfigPanel(cogButton, id) {
//     cogButton.closest('.grid-stack-item').find(".panel-body:first").toggle();
//     $("#form_" + id).toggle();
// }
//
// function enableGridChanges() {
//     // When a widget is moved or resized
//
//     grid = $('.grid-stack').data('gridstack');
//     grid.enable();
// }
