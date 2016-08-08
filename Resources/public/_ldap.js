var cache = {};
var users = {};
$(document).on('keyup', 'input[data-ldap-field]', function () {
    var $field = $(this);
    if (!$field.hasClass('autocomplete')) {
        $field.autocomplete({
            source: function (request, response) {
                var $fields = $('[data-ldap-group="' + $field.data('ldap-group') + '"]');
                var fieldData = {};
                $fields.each(function () {
                    var $loopField = $(this);
                    fieldData[$loopField.data('ldap-field')] = $loopField.val();
                });
                var hash = JSON.stringify(fieldData);
                if (hash in cache) {
                    response(cache[hash]);
                    return;
                }
                $.ajax({
                    url: "{{ path('gbmec2_ldap_search') }}",
                    dataType: "json",
                    data: fieldData,
                    success: (function (hash) {
                        return function (data) {
                            $.each(data, function (index, user) {
                                if ('undefined' != typeof user['username']) {
                                    data[index] = {
                                        label: user['firstName'] + ' ' + user['lastName'] + ' ' + user['email'],
                                        value: user['username']
                                    };
                                    users[user['username']] = user;
                                }
                            });
                            delete data['count'];
                            cache[hash] = data;
                            response(data);
                        };
                    }(hash))
                });
            },
            delay: 500,
            minLength: 2,
            select: function (event, ui) {
                var username = (ui.item ? ui.item.value : null);
                var user = (username ? users[username] : null);
                if (user) {
                    var $fields = $('[data-ldap-group="' + $(this).data('ldap-group') + '"]');
                    $fields.each(function () {
                        var $loopField = $(this);
                        var name = $loopField.data('ldap-field');
                        if ('undefined' != typeof user[name]) {
                            $loopField.val(user[name]);
                        }
                    });
                }
            },
            open: function () {
                $(this).removeClass("ui-corner-all").addClass("ui-corner-top");
            },
            close: function () {
                $(this).removeClass("ui-corner-top").addClass("ui-corner-all");
            }
        });
        $field.addClass('autocomplete');
    }
});
