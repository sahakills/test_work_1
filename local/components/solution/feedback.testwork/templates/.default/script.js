$(function (){
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
    $('form[name="feedback"]').on('submit', function (e) {
        e.preventDefault()
        let formData = new FormData(this),
            $form = $(this)
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
        })
    })
    function updateCompoundIndiex() {
        $('#requestItems .request-item').each(function(index) {
            $(this).find('select[name^="COMPOUND"]').attr('name', `COMPOUND[${index}][BRAND]`);
            $(this).find('input[name^="COMPOUND"][name*="[NAME]"]').attr('name', `COMPOUND[${index}][NAME]`);
            $(this).find('input[name^="COMPOUND"][name*="[COUNT]"]').attr('name', `COMPOUND[${index}][COUNT]`);
            $(this).find('input[name^="COMPOUND"][name*="[PACK]"]').attr('name', `COMPOUND[${index}][PACK]`);
        });
    }
})