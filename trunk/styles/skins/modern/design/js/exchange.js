(function($, uAdmin) {
    $(function() {
        var data = uAdmin.data.data;
        var encodingFieldName = 'encoding_' + data['object-type'];
        var formatFieldName = 'format';

        loadFieldOptions(encodingFieldName, function() {
            loadFieldOptions(formatFieldName, function() {
                var encodingSelectize = getFieldSelectize(encodingFieldName);
                var selectedEncodingId = encodingSelectize.getValue();
                var encodingOptionValue = $('select[name*=' + formatFieldName + '] option:selected').val();
                var selectedEncoding = null;

                if (selectedEncodingId) {
                    selectedEncoding = encodingSelectize.getItem(selectedEncodingId)[0].innerHTML;
                }

                if (!selectedEncoding) {
                    // Получаем кодировку по умолчанию
                    var defaultEncoding = data['default-encoding'];
                    if (defaultEncoding.toLowerCase() === 'cp1251' || !defaultEncoding) {
                        defaultEncoding = 'Windows-1251';
                    }

                    var defaultOption = _.find(_.values(encodingSelectize.options), function(option) {
                        return option.text.toLowerCase() == defaultEncoding.toLowerCase();
                    });

                    encodingSelectize.setValue(defaultOption.value);
                }

                var formatSelectize = getFieldSelectize(formatFieldName);

                // Блок поля "Кодировка"
                var $encodingSelect = $('select[name*=' + encodingFieldName + ']');
                var $encodingFieldBlock = $encodingSelect.closest('div.relation');
                var csvOptionId = data['csv-format-id'];

                if (encodingOptionValue == csvOptionId) {
                    $encodingFieldBlock.show();
                } else {
                    $encodingFieldBlock.hide();
                }

                formatSelectize.on('change', function(formatId) {
                    if (formatId == csvOptionId) {
                        $encodingFieldBlock.show();
                    } else if (formatId) {
                        $encodingFieldBlock.hide();
                    }
                });

            })
        });

        /**
         * Загружает доступные элементы для поля типа "Выпадающий список"
         * @param {String} fieldName имя поля
         * @param {Function} callback выполняется после успешной загрузки элементов в DOM
         */
        function loadFieldOptions(fieldName, callback) {
            var fieldSelect = $('select[name*=' + fieldName + ']');
            var fieldContainer = fieldSelect.closest('div.relation');

            var control = new ControlRelation({
                container: fieldContainer,
                type: fieldContainer.attr("umi:type"),
                id: fieldContainer.attr("id"),
                empty: (fieldContainer.attr("umi:empty") === "empty")
            });

            control.loadItemsAll(callback);
        }

        /**
         * Возвращает объект selectize поля
         * @param {String} fieldName имя поля
         * @returns {*}
         */
        function getFieldSelectize(fieldName) {
            var fieldSelect = $('select[name*=' + fieldName + ']');
            return fieldSelect.data().selectize;
        }

    });

}(jQuery, uAdmin));