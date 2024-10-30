jQuery(document).ready(function ($) {
    // Скрываем стандартные списки <select>
    $('.variations_form select').hide();

    $('.variation-thumbnail-button').on('click', function () {
        var variationId = $(this).data('variation_id');
        var attributeName = $(this).data('attribute_name');
        var form = $('.variations_form');

        // Устанавливаем нужный атрибут вариации и запускаем обновление
        // Ищем и устанавливаем нужную вариацию
        form.find('select').val(attributeName).trigger('change');
        form.find('input.variation_id').val(variationId).change();
        form.trigger('woocommerce_variation_select_change');
        form.trigger('show_variation');

        // Обновляем активный стиль кнопки
        $('.variation-thumbnail-button').removeClass('selected');
        $(this).addClass('selected');
    });
});