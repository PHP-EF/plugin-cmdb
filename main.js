// Listener to add changed class to modal settings elements
var changedCMDBModalElements = new Set();
$("body").on('change, input', '#CMDBModal .info-field', function(event) {
    const elementName = $(this).data('label');
    if (!changedCMDBModalElements.has(elementName)) {
        toast("Configuration", "", elementName + " has changed.<br><small>Save configuration to apply changes.</small>", "warning");
        changedCMDBModalElements.add(elementName);
    }
    $(this).addClass("changed");
});

$("body").on('change', '#columnFieldType', function(event) {
    if ($(this).val() == "SELECT") {
        $("#columnSelectOptions").parent().attr('hidden',false);
    } else {
        $("#columnSelectOptions").parent().attr('hidden',true);
    }
});