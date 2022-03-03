{% extends '@LleDashboard/widget/base_widget.html.twig' %}

{% block widget_body %}
    <div class="row">
        {% for state in states %}
            <div class="col text-center">
                <h2>{{ state.count }}</h2>
                {{ ('workflow.' ~ state.<?= $workflow ?>)|trans }}
            </div>
        {% endfor %}
    </div>
{% endblock %}