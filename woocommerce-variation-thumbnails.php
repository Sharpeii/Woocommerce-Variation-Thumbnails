<?php
/*
WooCommerce Variation Thumbnails
Description: Добавляет миниатюры изображений для вариативных товаров в WooCommerce.
Author: Sharpeii
* Для подключения используем require get_template_directory() . '/inc/woocommerce-variation-thumbnails.php';
*/

//Метаполя для миниатюр цветов

global $product;

// Добавляем кнопку загрузки миниатюры и превью изображений на странице вариативных товаров
add_action('woocommerce_variation_options_pricing', 'add_variation_image_thumbnail_field', 10, 3);
function add_variation_image_thumbnail_field($loop, $variation_data, $variation): void
{
    $meta_key = 'variation_image_thumbnail';
    $image_id = get_post_meta($variation->ID, $meta_key, true); // Получаем ID изображения
    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : ''; // Получаем URL миниатюры, если изображение есть
    ?>
    <div class="variation-image-upload-field form-row form-row-full">
        <label><?php _e('Миниатюра вариации', 'woocommerce'); ?></label>
        <button
            type="button"
            class="button upload_variation_image_button"
            data-input-id="variation_image_thumbnail_<?php echo $variation->ID; ?>">
            <?php echo $image_url ? __('Изменить изображение', 'woocommerce') : __('Загрузить изображение', 'woocommerce'); ?>
        </button>
        <input
            type="hidden"
            id="variation_image_thumbnail_<?php echo $variation->ID; ?>"
            name="variation_image_thumbnail_<?php echo $variation->ID; ?>"
            value="<?php echo esc_attr($image_id); ?>" />

        <!-- Превью изображения -->
        <div class="variation-image-preview" style="margin-top: 10px;">
            <?php if ($image_url): ?>
                <img src="<?php echo esc_url($image_url); ?>" style="width: 60px; height: auto;" alt="img"/>
            <?php endif; ?>
        </div>
    </div>

    <?php
}

// Сохранение id изображения миниатюры при сохранении вариации
add_action('woocommerce_save_product_variation', 'save_variation_image_thumbnail_field', 10, 2);
function save_variation_image_thumbnail_field($variation_id, $i): void
{
    $meta_key = 'variation_image_thumbnail';
    if (isset($_POST[$meta_key . '_'  . $variation_id])) {
        $image_id = absint($_POST[$meta_key . '_'  . $variation_id]);
        update_post_meta($variation_id, $meta_key, $image_id); // Сохраняем ID изображения
    }
}

//Debug
//add_action('woocommerce_product_after_variable_attributes', 'debug_variation_image_thumbnail_field', 20, 3);
//function debug_variation_image_thumbnail_field($loop, $variation_data, $variation): void
//{
//    $meta_value = get_post_meta($variation->ID, 'variation_image_thumbnail', true);
//    error_log('ID изображения для вариации ' . $variation->ID . ': ' . print_r($meta_value, true));
//}

// Вывод миниатюр изображений вариаций на фронтенде
// Проверяем, возвращает ли wp_get_attachment_image_src() массив перед использованием
add_filter('woocommerce_available_variation', 'display_variation_image_button');
function display_variation_image_button($variation) {
    $meta_key = 'variation_image_thumbnail';
    $variation_id = $variation['variation_id'];
    $thumbnail_id = get_post_meta($variation_id, $meta_key, true); // Получаем ID миниатюры

    // Проверка, что ID изображения получен и его можно использовать
    if ($thumbnail_id && is_numeric($thumbnail_id)) {
        $thumbnail_src = wp_get_attachment_image_src($thumbnail_id, 'thumbnail');

        // Проверка, что wp_get_attachment_image_src вернула массив
        if ($thumbnail_src && is_array($thumbnail_src)) {
            $variation['variation_thumbnail_button'] = $thumbnail_src[0]; // URL миниатюры
        }
    }

    return $variation;
}

// Вывод миниатюр на странице товара
add_action('woocommerce_before_variations_form', 'render_variation_thumbnail_buttons');
function render_variation_thumbnail_buttons(): void
{
    global $product;

    if ($product->is_type('variable')) {
        $variations = $product->get_available_variations();
        $attribute_name = 'attribute_pa_color'; // Замените на имя вашего атрибута, например, 'attribute_pa_color'
        echo '<div class="variation-thumbnails">';
        foreach ($variations as $variation) {
            $meta_key = 'variation_image_thumbnail';

            $variation_id = $variation['variation_id'];
            $thumbnail_id = get_post_meta($variation_id, $meta_key, true); // Получаем ID миниатюры

// Проверка корректности ID и получения URL изображения

            if ($thumbnail_id && is_numeric($thumbnail_id)) {
                $thumbnail_src = wp_get_attachment_image_src($thumbnail_id, 'thumbnail');

                if ($thumbnail_src && is_array($thumbnail_src)) {
                    $thumbnail_url = esc_url($thumbnail_src[0]);
                    echo '<button
                            class="variation-thumbnail-button"
                            data-variation_id="' . esc_attr($variation_id) . '"
                            data-attribute_name="' . esc_attr($variation['attributes'][$attribute_name]) . '">
                            <img src="' . $thumbnail_url . '" alt="Вариация" />
                          </button>';
                } else {
                    // Отладочное сообщение, если URL не получен
                    echo '<!-- Ошибка: миниатюра не получена для ID ' . $thumbnail_id . ' -->';
                }
            } else {
                echo '<!-- Ошибка: Неверный ID миниатюры для вариации ' . $variation_id . ' -->';

            }
        }
        echo '</div>';
    }
}

// Подключение скрипта и стилей
add_action('wp_enqueue_scripts', 'enqueue_variation_thumbnail_scripts');
function enqueue_variation_thumbnail_scripts(): void
{
    if (is_product()) {
        wp_enqueue_script('variation-thumbnails', get_template_directory_uri() . '/assets/js/variation-thumbnails.js', array('jquery'), '', true);

        // Добавление стилей для кнопок
        wp_add_inline_style('theme-style', '
            .variation-thumbnails {
                display: flex;
                gap: 30px;
                margin-bottom: 20px;
            }
            .variation-thumbnail-button {
                border: 2px solid transparent;
                padding: 3px;
                cursor: pointer;
                background-color: transparent;
            }
            .variation-thumbnail-button.selected {
                border-color: #000;
            }
            .variation-thumbnail-button img {
                width: 40px;
                height: auto;
            }
        ');
    }
}
// Добавляем скрипт медиазагрузчика внизу страницы админки
add_action('admin_footer', 'variation_image_thumbnail_script');
function variation_image_thumbnail_script(): void
{
    // Проверяем, что мы находимся на странице редактирования продукта
    $screen = get_current_screen();
    if ($screen->id !== 'product') return;

    // Подключаем медиазагрузчик WordPress
    wp_enqueue_media();
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            console.log('Script loaded'); // Проверка, загружается ли скрипт

            // Открываем медиазагрузчик по клику на кнопку "Загрузить изображение"
            $(document).on('click', '.upload_variation_image_button', function(e) {
                e.preventDefault();

                console.log('Input clicked'); // Проверка, кликается ли поле

                const button = $(this);
                const inputField = $('#' + button.data('input-id'));
                const previewContainer = button.siblings('.variation-image-preview');

                // Открываем медиазагрузчик WordPress
                const customUploader = wp.media({
                    title: 'Выберите изображение для вариации',
                    button: {
                        text: 'Использовать это изображение'
                    },
                    multiple: false
                }).on('select', function() {
                    const attachment = customUploader.state().get('selection').first().toJSON();
                    inputField.val(attachment.id); // Устанавливаем ID изображения в скрытое поле
                    button.text('Изменить изображение'); // Меняем текст кнопки

                    // Обновляем или добавляем превью изображения
                    previewContainer.html('<img src="' + attachment.url + '" style="width: 60px; height: auto;" alt="img"/>');
                }).open();
            });
        });
    </script>
    <?php
}