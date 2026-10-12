<?php

/**
 * Modestox CMS - E-commerce Platform
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   AGPL-3.0-or-later
 * @link      https://github.com/Modestox/modestox
 */
class Modestox_DeltaLog_Block_Adminhtml_Rule_Edit_Tab_Instruction extends Mage_Adminhtml_Block_Widget_Form implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /**
     * Render instruction tab content HTML
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        $helper = Mage::helper('modestox_deltalog');

        return '
        <div class="entry-edit">
            <div class="entry-edit-head">
                <h4 class="icon-head head-edit-form fieldset-legend">' . $helper->__('Руководство по настройке правил отслеживания (Delta Log)') . '</h4>
            </div>
            <div class="fieldset fieldset-wide">
                <div style="font-size: 13px; line-height: 1.6; color: #2f2f2f;">

                    <h3 style="color: #ea7601; margin-top: 10px; border-bottom: 1px solid #ccc; padding-bottom: 3px;">1. Зачем нужна эта настройка?</h3>
                    <p>' . $helper->__('Модуль <strong>Delta Log</strong> фиксирует точечные изменения (дельты) данных в системе: кто (имя администратора), когда, с какого IP-адреса и в каком конкретно поле изменил значение (<code>было &rarr; стало</code>).') . '</p>
                    <p>' . $helper->__('Чтобы модуль работал с максимальной производительностью и не нагружал базу данных лишними проверками при каждом сохранении объектов, правила логирования компилируются в быстрый кэш OpenMage.') . '</p>

                    <h3 style="color: #ea7601; margin-top: 20px; border-bottom: 1px solid #ccc; padding-bottom: 3px;">2. Описание полей формы и порядок заполнения</h3>

                    <h4 style="margin-bottom: 5px; color: #0a4386;">Основная конфигурация (General Configuration)</h4>
                    <ul style="list-style-type: disc; margin-left: 20px;">
                        <li><strong>Rule Title (Название правила):</strong> ' . $helper->__('Произвольное понятное название правила для списка в админке (например: <em>Отслеживание данных покупателя</em>).') . '</li>
                        <li><strong>Status (Статус):</strong> ' . $helper->__('Включает (<code>Enabled</code>) или отключает (<code>Disabled</code>) правило. Отключенные правила не компилируются в кэш.') . '</li>
                        <li><strong>Entity Type (Тип сущности):</strong> ' . $helper->__('Определяет внутреннюю структуру хранения данных в базе. Выберите <strong>EAV Model</strong> или <strong>Flat Table</strong>.') . '</li>
                        <li><strong>Database Table / Entity Code (Имя таблицы / Код сущности) <span class="required">*</span>:</strong> ' . $helper->__('Главный ключ привязки к событию сохранения.') . '<br/>
                            &bull; <em>Flat Table:</em> ' . $helper->__('точное название таблицы в MySQL (например: <code>sales_flat_order</code>).') . '<br/>
                            &bull; <em>EAV Model:</em> ' . $helper->__('код главной таблицы сущности (например: <code>customer_entity</code> для покупателей или <code>catalog_product_entity</code> для товаров).') . '
                        </li>
                        <li><strong>Model Alias (Псевдоним модели):</strong> ' . $helper->__('Алиас модели OpenMage для идентификации объекта (например: <code>customer/customer</code>, <code>catalog/product</code>, <code>sales/order</code>).') . '</li>
                        <li><strong>Title Column Field (Поле заголовка сущности):</strong> ' . $helper->__('Поле, значение которого отображается в логах вместо ID (например: <code>email</code> для Покупателей, <code>sku</code> для Товаров, <code>increment_id</code> для Заказов).') . '</li>
                        <li><strong>Admin Edit URL Route (Маршрут ссылки):</strong> ' . $helper->__('Путь страницы в админке для быстрого перехода к редактированию (например: <code>adminhtml/customer/edit</code>).') . '</li>
                    </ul>

                    <h4 style="margin-bottom: 5px; margin-top: 15px; color: #0a4386;">Отслеживаемые поля (Tracked Fields)</h4>
                    <p>' . $helper->__('После сохранения основной информации перейдите во вкладку <strong>Tracked Fields</strong> и нажмите <strong>Fetch Fields (Загрузить поля)</strong>:') . '</p>
                    <ol style="margin-left: 20px;">
                        <li>' . $helper->__('Инспектор автоматически вытянет доступные колонки или атрибуты.') . '</li>
                        <li>' . $helper->__('Включите чекбоксы у полей, изменения в которых нужно записывать (например: <code>firstname</code>, <code>email</code>).') . '</li>
                        <li>' . $helper->__('Для полей со справочниками (группы, статусы) можно указать Source Model, чтобы в логах выводились названия (<code>General</code>) вместо ID (<code>1</code>).') . '</li>
                    </ol>

                    <h3 style="color: #ea7601; margin-top: 20px; border-bottom: 1px solid #ccc; padding-bottom: 3px;">3. В чем разница между Flat Table и EAV Model?</h3>

                    <table style="width: 100%; border-collapse: collapse; margin-top: 10px; background: #fff;" border="1" cellpadding="8" cellspacing="0">
                        <thead>
                            <tr style="background-color: #f6f6f6; text-align: left;">
                                <th style="width: 20%;">Тип модели</th>
                                <th style="width: 40%;">Как устроена</th>
                                <th style="width: 40%;">Примеры в OpenMage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Flat Table</strong><br/><em>(Плоская таблица)</em></td>
                                <td>' . $helper->__('Все данные одной сущности хранятся в <strong>одной физической таблице</strong>. Каждое поле &mdash; отдельная колонка MySQL. Простая и быстрая структура.') . '</td>
                                <td>
                                    &bull; <code>sales_flat_order</code> (Заказы)<br/>
                                    &bull; <code>salesrule</code> (Промокоды / Скидки)<br/>
                                    &bull; <code>admin_user</code> (Администраторы)
                                </td>
                            </tr>
                            <tr>
                                <td><strong>EAV Model</strong><br/><em>(Entity-Attribute-Value)</em></td>
                                <td>' . $helper->__('Данные распределены по <strong>многим таблицам</strong>. В главной (<code>*_entity</code>) хранятся системные ID, а атрибуты &mdash; в таблицах типов (<code>*_varchar</code>, <code>*_int</code>). Позволяет добавлять атрибуты без изменения структуры БД.') . '</td>
                                <td>
                                    &bull; <code>customer_entity</code> (Покупатели)<br/>
                                    &bull; <code>catalog_product_entity</code> (Товары)<br/>
                                    &bull; <code>catalog_category_entity</code> (Категории)
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <h3 style="color: #ea7601; margin-top: 20px; border-bottom: 1px solid #ccc; padding-bottom: 3px;">💡 Чек-лист проверки после создания правила:</h3>
                    <ul style="list-style-type: check; margin-left: 20px;">
                        <li>1. ' . $helper->__('Убедитесь, что в поле <strong>Database Table / Entity Code</strong> указано точное имя таблицы (например, <code>customer_entity</code>).') . '</li>
                        <li>2. ' . $helper->__('Нажмите <strong>Save Rule</strong> &mdash; компилятор автоматически пересоберет кэш.') . '</li>
                        <li>3. ' . $helper->__('Измените поле у объекта (например, имя клиента) и сохраните его.') . '</li>
                        <li>4. ' . $helper->__('Проверьте раздел <strong>System &rarr; Delta Log &rarr; Change Log History</strong> &mdash; там появится новая запись!') . '</li>
                    </ul>

                </div>
            </div>
        </div>';
    }

    /**
     * Get tab label
     *
     * @return string
     */
    public function getTabLabel(): string
    {
        return Mage::helper('modestox_deltalog')->__('Instruction');
    }

    /**
     * Get tab title
     *
     * @return string
     */
    public function getTabTitle(): string
    {
        return Mage::helper('modestox_deltalog')->__('Instruction & Usage Guide');
    }

    /**
     * Can show tab
     *
     * @return bool
     */
    public function canShowTab(): bool
    {
        return true;
    }

    /**
     * Is tab hidden
     *
     * @return bool
     */
    public function isHidden(): bool
    {
        return false;
    }
}
