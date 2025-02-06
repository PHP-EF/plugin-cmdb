// Listener to add changed class to modal settings elements
var changedCMDBModalElements = new Set();
$("body").on('change', '#CMDBModal .info-field', function(event) {
    const elementName = $(this).data('label');
    if (!changedCMDBModalElements.has(elementName)) {
        toast("Configuration", "", elementName + " has changed.<br><small>Save configuration to apply changes.</small>", "warning");
        changedCMDBModalElements.add(elementName);
    }
    $(this).addClass("changed");
});