/**
 * Разворачиваемый/сворачиваемый многострочный текст.
 *
 * Делегированный клик по .expandable-text__toggle:
 *  - свёрнуто → развёрнуто: сохраняет обрезанный текст в data-truncated,
 *    подставляет полный (data-full), меняет подпись на «свернуть»;
 *  - развёрнуто → свёрнуто: возвращает обрезку, подпись «показать».
 *
 * Работает для всех виджетов на странице, в т.ч. добавленных динамически.
 * Подключается глобально в layouts/admin.blade.php.
 */
$(function () {
    $(document).on('click', '.expandable-text__toggle', function (e) {
        e.preventDefault();

        var $toggle = $(this);
        var $preview = $toggle.siblings('.expandable-text__preview');

        if ($preview.attr('data-expanded') === '1') {
            $preview.text($preview.attr('data-truncated')).attr('data-expanded', '0');
            $toggle.text('показать');
        } else {
            $preview.attr('data-truncated', $preview.text());
            $preview.text($preview.attr('data-full')).attr('data-expanded', '1');
            $toggle.text('свернуть');
        }
    });
});
