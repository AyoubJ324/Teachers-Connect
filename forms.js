/**
  * Return a form inputs in a key-value pair. The divID is the id of the div containing the form
  * and the selectors is the types of inputs to take, e.g. 'input,textarea' will search <input> tags and <textarea>
  */
function serializeForm(divID, selectors) {
  var div = document.getElementById(divID);

  if (div != null) {
    var inputs = div.querySelectorAll(selectors);

    if (inputs.length > 0) {
      var data = {};
      for (var i = 0; i < inputs.length; i++) {
        var input = inputs[i];

        if (input.nodeName.toLowerCase() == 'select' && input.multiple) {
          var options = {};
          for (var j = 0, l = input.options.length; j < l; j++) {
            var o = input.options[j];

            if (o.selected) {
              options[j] = o.value;
            }
          }

          data[input.id] = options;
        } else {
          data[input.id] = input.value;
        }
      }

      return data;
    }
  }

  return null;
}

/**
  * Validated the form, returning true if valid, false if not
  */
function validateForm(formId) {
  var form = document.getElementById(formId);

  if (form != null) {
    var valid = form.checkValidity();
    form.classList.add('was-validated');
    return valid;
  }

  return false;
}

/**
  * Clears any validation from the provided form if it exists
  */
function clearValidation(formId) {
  var form = document.getElementById(formId);

  if (form != null) {
    setTimeout(function() {
      form.classList.remove('was-validated');}, 2000);
  }
}

/**
  * Remove the value from the select identified by select id if it is found
  */
function removeFromSelectByValue(selectId, value) {
  var select = document.getElementById(selectId);

  if (select != null) {
    for (var i = 0; i < select.length; i++) {
      if (select.options[i].value == value) {
        select.remove(i);
        return;
      }
    }
  }
}

/**
  * Adds a new option to the select identified by the id.
  * If value is null, text is taken to be the value
  */
function addToSelect(selectId, value, text) {
  var select = document.getElementById(selectId);

  if (select != null) {
    var option = document.createElement("option");
    option.text = text;
    if (value != null) {
      option.value = value;
    }

    select.add(option);
  }
}

const alerts = {};

/**
  * Create and return the alert element
  */
function createAlertElement(success, success_message, form_id) {
  if (alerts[form_id] != null) {
    alerts[form_id].remove();
  }

  var alertContainer = document.createElement("div");

  var alertType = success ? 'alert-success':'alert-danger';
  alertContainer.classList.add('row', 'm-auto', 'mt-2', 'alert', alertType, 'alert-dismissable', 'fade', 'show');
  alertContainer.setAttribute('role', 'alert');

  var text = document.createTextNode(success_message);
  alertContainer.appendChild(text);

  var buttonContainer = document.createElement("div");
  buttonContainer.classList.add('col', 'text-end');
  var button = document.createElement("button");
  button.type = "button";
  button.classList.add('btn-close');
  button.setAttribute('data-bs-dismiss', 'alert');
  button.setAttribute('aria-label', 'Close');
  buttonContainer.appendChild(button);

  alertContainer.appendChild(buttonContainer);

  alerts[form_id] = alertContainer;
  return alertContainer;
}

/**
  * Adds an alert message to the specified form
  */
function addAlertMessage(success, success_message, form_id) {
  var form = document.getElementById(form_id);

  if (form != null) {
    form.appendChild(createAlertElement(success, success_message, form_id));
  }
}
