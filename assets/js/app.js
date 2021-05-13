import "../css/app.scss";

import 'gridstack/dist/gridstack.min.css';
import {GridStack} from 'gridstack';
import 'gridstack/dist/h5/gridstack-dd-native';

$(function () {
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
    let grid = GridStack.init({
        animate: true,
        float: true
    });

    let items = $('.grid-stack').data('widgets');
    grid.on('added', function (event, items) {
        $('.widget-close').click(function (e) {
            var id = this.getAttribute("data-widgetid");
            console.log($('#widget_'+id));
            grid.removeWidget('[gs-id="'+id+'"]');
            $.ajax(Routing.generate('remove_widget', {id: id}));

        });
    });
    // save changes
    grid.on('change', function (event, items) {
        console.log('change', items);
        items.forEach(function (item) {
            $.ajax(Routing.generate('update_widget', {id: item.id, x: item.x, y: item.y, width: item.w, height: item.h}));
        });
    });

    grid.load(items);/*, (g,w,add)=>{
        console.log(w);
    });*/


    // add widget button
    $(".add-widget").click((e) => {
        console.log(e);
        let type = $(e.target).data('type');
        $.ajax({
            url: Routing.generate('add_widget', {type: type}),
            success: function (response) {
                grid.addWidget(response);
            },
            complete: function (response) {
                toggleSpin();
            }
        });
    })
    /*$('.grid-stack').gridstack({
        width: 12,
        animate: true,
        float: true,
        handleClass: 'panel-heading',
        alwaysShowResizeHandle: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
        resizable: {
            // Don't put handles on the sizes so the user can still interact with scroll bars
            handles: 'se, sw, ne, nw'
        }
    });


    grid = $('.grid-stack').data('gridstack');
    grid.disable();*/
});

// Edit the title of a widget.
function editTitle(title) {

    // SCENE
    var container = title.closest('div');
    var newContainer = container.clone(true);

    // FORM CONTROL
    var input = $('<input></input>')
        .attr('type', 'text')
        .attr('value', title.text().trim())
        .addClass('pull-left form-control')
        .css('width', '15%')
        .css('min-width', '150px');
    var form = $('<form></form>').append(input).attr('action', '#');

    var id = $(title).closest('.grid-stack-item').data('widget-id');

    // user presses enter
    form.submit(function () {
        $(newContainer).find('span#widget_title').text(input.val()).append("&nbsp;");
        $.ajax(Routing.generate('update_title', {id: id, title: input.val()}));
        form.replaceWith(newContainer);
    })

    // user focuses out
    input.blur(function () {
        $(newContainer).find('span#widget_title').text(input.val()).append("&nbsp;");
        $.ajax(Routing.generate('update_title', {id: id, title: input.val()}));
        form.replaceWith(newContainer);
    })

    // show input and focus
    container.replaceWith(form);
    input.focus()[0].setSelectionRange(99999, 99999);
    ;
}

function toggleSpin() {
    $("#gs-spin").toggleClass("fa-circle-o-notch");
}

function toggleConfigPanel(cogButton, id) {
    cogButton.closest('.grid-stack-item').find(".panel-body:first").toggle();
    $("#form_" + id).toggle();
}

function enableGridChanges() {
    // When a widget is moved or resized

    grid = $('.grid-stack').data('gridstack');
    grid.enable();
}
