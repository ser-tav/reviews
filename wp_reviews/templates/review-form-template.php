<?php
$options = get_option('reviews_settings_options');
$instance = new Reviews();
?>

<div class="review-form">

    <input type="text" id="review_heading" name="review_heading" placeholder="<?php esc_html_e('Заголовок *', 'reviews') ?>" required="required">

    <input type="text" id="review_name" name="review_name" placeholder="<?php esc_html_e('Имя *', 'reviews') ?>" required="required">

    <textarea id="review_content" name="review_content" cols="30" rows="10" placeholder="<?php esc_html_e('Отзыв', 'reviews') ?>"></textarea>

    <input type="text" id="review_social" name="review_social" placeholder="<?php esc_html_e('Ссылка на соцсети *', 'reviews') ?>" required="required">

    <button id="add-review"><?php esc_html_e('Отправить отзыв', 'reviews') ?></button>

    <div class="message-container"></div>
</div>
