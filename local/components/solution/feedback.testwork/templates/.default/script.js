$(function () {
    $(document).on('click', '.add-row', function (e) {
        e.preventDefault()
        var row = $(this).closest('.request-item').clone();
        row.find('input').val('');
        $('#requestItems').append(row);
        updateCompoundIndiex();
    });

    $(document).on('click', '.remove-row', function (e) {
        e.preventDefault()
        if ($('.request-item').length > 1) {
            $(this).closest('.request-item').remove();
            updateCompoundIndiex();
        }

    });
    updateCompoundIndiex();
    let $form = $('form[name="feedback"]')
    $form.attr('novalidate', true);
    $form.on('submit', function (e) {
        e.preventDefault()
        e.stopPropagation()
        let formData = new FormData(this),
            $form = $(this),
            requiredFields = [
                "FIELDS[NAME]",
                "FIELDS[CATEGORY]",
                "FIELDS[TYPE]",
            ];
        if (validateFormData(formData, requiredFields, $form)) {
            BX.ajax.runComponentAction(
                'solution:feedback.testwork',
                'feedbackSend',
                {
                    mode: 'class',
                    data: formData,
                }
            ).then(function (response) {
                    if (response.data === true) {
                        $form.hide()
                        $('.feedback-answer').show()
                    }
                }, function (response) {
                    response.errors.forEach(function (value) {
                        $form.find('.errors').append(
                            `<div class="alert alert-danger" role="alert">\n` +
                            `${value.message}` +
                            `</div>`
                        )
                    })
                }
            );
        }
    })

    function updateCompoundIndiex() {
        $('#requestItems .request-item').each(function (index) {
            $(this).find('select[name^="COMPOUND"]').attr('name', `COMPOUND[${index}][BRAND]`);
            $(this).find('input[name^="COMPOUND"][name*="[NAME]"]').attr('name', `COMPOUND[${index}][NAME]`);
            $(this).find('input[name^="COMPOUND"][name*="[COUNT]"]').attr('name', `COMPOUND[${index}][COUNT]`);
            $(this).find('input[name^="COMPOUND"][name*="[PACK]"]').attr('name', `COMPOUND[${index}][PACK]`);
        });
    }
})

function validateFormData(formData, requiredFields, $form) {
    let isValid = true;
    requiredFields.forEach(function (fieldName) {
        const value = formData.get(fieldName);
        const isFile = fieldName.endsWith("[]");

        let $input = $form.find(`[name="${fieldName}"]`);

        if ($input.length > 1 && $input.attr('type') === 'radio') {
            const checked = $form.find(`[name="${fieldName}"]:checked`);
            if (checked.length === 0) {
                isValid = false;
            }
            return;
        }

        if (isFile) {
            let files = formData.getAll(fieldName);
            const empty = files.length === 0 || (files.length === 1 && files[0].name === '');
            if (empty) {
                isValid = false;
            }
        } else {
            if (!value || value.trim() === "") {
                isValid = false;
            }
        }
    });

    if (!isValid) {
        $form.addClass('was-validated')
    } else {
        $form.find('.errors').empty()
        $form.removeClass('was-validated')
    }

    return isValid;
}
