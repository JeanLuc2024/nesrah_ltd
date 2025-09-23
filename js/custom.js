/*------------------------------------------------------------------
    File Name: custom.js
    Template Name: NESRAH GROUP Management System
    Version: 1.0
-------------------------------------------------------------------*/

/*--------------------------------------
	sidebar
--------------------------------------*/

"use strict";

$(document).ready(function () {
  /*-- sidebar js --*/
  $('#sidebarCollapse').on('click', function () {
    $('#sidebar').toggleClass('active');
  });
  
  /*-- tooltip js --*/
  $('[data-toggle="tooltip"]').tooltip();
  
  /*-- dropdown js --*/
  $('.dropdown-toggle').dropdown();
});

/*--------------------------------------
    scrollbar js
--------------------------------------*/

var ps = new PerfectScrollbar('#sidebar');

/*--------------------------------------
    form validation
--------------------------------------*/

function validateForm(formId) {
    var form = document.getElementById(formId);
    if (form.checkValidity() === false) {
        event.preventDefault();
        event.stopPropagation();
    }
    form.classList.add('was-validated');
}

/*--------------------------------------
    utility functions
--------------------------------------*/

function showAlert(message, type = 'info') {
    var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                    message +
                    '<button type="button" class="close" data-dismiss="alert">' +
                    '<span>&times;</span>' +
                    '</button>' +
                    '</div>';
    
    // Insert at the top of the content area
    var content = document.querySelector('.midde_cont');
    if (content) {
        content.insertAdjacentHTML('afterbegin', alertHtml);
    }
}

function confirmAction(message) {
    return confirm(message);
}

/*--------------------------------------
    auto-hide alerts
--------------------------------------*/

$(document).ready(function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        if (typeof $('.alert').fadeOut === 'function') {
            $('.alert').fadeOut();
        } else {
            // fallback: just hide alerts
            $('.alert').hide();
        }
    }, 5000);
    
    // Initialize calendar if it exists
    if (typeof $.fn.calendar !== 'undefined') {
        $('.ui.calendar').calendar();
    }
    
    // Initialize charts if Chart.js is available
    if (typeof Chart !== 'undefined') {
        // Chart initialization will be handled by individual pages
        console.log('Chart.js loaded successfully');
    }
});

/*--------------------------------------
    AJAX helper functions
--------------------------------------*/

function makeAjaxRequest(url, data, method = 'POST', successCallback = null, errorCallback = null) {
    if (typeof $ === 'undefined' || typeof $.ajax === 'undefined') {
        console.error('jQuery or $.ajax is not available');
        if (errorCallback) errorCallback('jQuery not loaded');
        return;
    }
    
    $.ajax({
        url: url,
        method: method,
        data: data,
        dataType: 'json',
        success: function(response) {
            if (successCallback) {
                successCallback(response);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            if (errorCallback) {
                errorCallback(error);
            } else {
                showAlert('An error occurred: ' + error, 'danger');
            }
        }
    });
}