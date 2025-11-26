/**
 * @package     CyberSalt.Whmcsbridge
 * @subpackage  Administrator
 *
 * @copyright   Copyright (C) 2024 CyberSalt. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// WHMCS Bridge Admin JavaScript
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // Add confirmation to sync buttons
    const syncButtons = document.querySelectorAll('a[href*="task=sync"]');
    syncButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to run this sync? This may take a while for large datasets.')) {
                e.preventDefault();
            }
        });
    });
});
