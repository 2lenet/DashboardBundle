{% extends '@LleDashboard/widget/base_widget.html.twig' %}

{% block widget_body %}
<div class="row">
    {% for state in states %}
<?php if ($withLink) { ?>
        <a class="col text-center text-decoration-none" href="{{ crudit_route_filtered_link('<?= strtolower($entity) ?>', {'<?= $workflow ?>': {'value': state.<?= $workflow ?>}}) }}">
            <h2>{{ state.count }}</h2>
            {{ ('workflow.' ~ state.<?= $workflow ?>)|trans }}
        </a>
<?php } else { ?>
        <div class="col text-center">
            <h2>{{ state.count }}</h2>
            {{ ('workflow.' ~ state.<?= $workflow ?>)|trans }}
        </div>
<?php } ?>
    {% endfor %}
</div>
{% endblock %}
