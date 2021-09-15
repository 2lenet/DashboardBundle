{% extends '@LleDashboard/widget/base_widget.html.twig' %}

{% block widget_body %}
<h2>{{ "widget.<?= $widgetname ?>.title"|trans }}</h2>
<div>Hello {{ data }} from the widget</div>
{% endblock %}
