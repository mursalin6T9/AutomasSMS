<?php

/**
 * Live SMS segment counter, mirroring AutomasSMS\Text::measure() in the browser.
 *
 * Bind by giving a textarea data-automassms-counter="<id of the output element>".
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

?>
<script>
    (function ($) {
        'use strict';

        var GSM_BASIC = '@£$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !"#¤%&\'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà',
            GSM_EXTENDED = '^{}\\[~]|€';

        function measure(value) {
            var chars = Array.from(value || ''),
                unicode = false,
                length = chars.length,
                i;

            for (i = 0; i < chars.length; i++) {
                if (GSM_BASIC.indexOf(chars[i]) === -1 && GSM_EXTENDED.indexOf(chars[i]) === -1) {
                    unicode = true;
                    break;
                }
            }

            if (!unicode) {
                for (i = 0; i < chars.length; i++) {
                    if (GSM_EXTENDED.indexOf(chars[i]) !== -1) {
                        length++;
                    }
                }
            }

            var single = unicode ? 70 : 160,
                concatenated = unicode ? 67 : 153,
                parts,
                perPart;

            if (length === 0) {
                return { encoding: unicode ? 'Unicode' : 'GSM', chars: 0, parts: 0, perPart: single, remaining: single };
            }

            if (length <= single) {
                parts = 1;
                perPart = single;
            } else {
                perPart = concatenated;
                parts = Math.ceil(length / concatenated);
            }

            return {
                encoding: unicode ? 'Unicode' : 'GSM',
                chars: length,
                parts: parts,
                perPart: perPart,
                remaining: (perPart * parts) - length
            };
        }

        function refresh(textarea) {
            var outputId = $(textarea).data('automassms-counter'),
                output = outputId ? document.getElementById(outputId) : null;

            if (!output) {
                return;
            }

            var result = measure(textarea.value);

            output.innerHTML = result.chars + ' characters &middot; '
                + result.parts + ' SMS &middot; '
                + result.remaining + ' left in this segment &middot; '
                + result.encoding + ' (' + result.perPart + '/segment)';

            $(output).toggleClass('is-over', result.parts > 1);
        }

        $(document).on('input change keyup paste', '[data-automassms-counter]', function () {
            refresh(this);
        });

        $(function () {
            $('[data-automassms-counter]').each(function () {
                refresh(this);
            });
        });
    })(jQuery);
</script>
