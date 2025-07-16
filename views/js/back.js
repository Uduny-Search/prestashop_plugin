/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a trade license awared by
 * PT. Jawa Kreatif Design.
 *
 * Any use, reproduction, modification or distribution
 * of this source file without the written consent of
 * PT. Jawa Kreatif Design It Is prohibited.
 *
 *  @author    UnusualDope <info@unusualdope.com>
 *  @copyright 2022 PT. Jawa Kreatif Design
 *  @license   Commercial license
 */

$(document).on('click', '.clickCopy', function () {
    var textToCopy = $(this).data('url'); // get the string

    // Create a temporary input element
    var tempInput = $("<input>");
    $("body").append(tempInput);
    tempInput.val(textToCopy).select();

    // Execute the copy command
    document.execCommand("copy");

    // Remove the temp input
    tempInput.remove();

    // Optional: change button text
    $(this).text('Copied!');
});
