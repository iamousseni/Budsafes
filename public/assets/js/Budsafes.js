class Budsafes {

  isElementInViewport(element) {
    //special bonus for those using jQuery
    if (typeof jQuery === "function" && element instanceof jQuery) {
      element = element[0];
    }

    let rect = element.getBoundingClientRect();

    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) && /*or $(window).height() */
        rect.right <= (window.innerWidth || document.documentElement.clientWidth) /*or $(window).width() */
    );
  }

  newRow(elementToAppend, URLNewRow, criteria) {
    //call controller that response the new row
    const budsafes = this;
    $.ajax({
      method: 'POST',
      url: URLNewRow,
      data: criteria
    }).done(function (response) {
      elementToAppend.append(response);
      let newRowElement = document.querySelector('.new-row');
      if(!budsafes.isElementInViewport(newRowElement))
        newRowElement.scrollIntoView({ behavior: 'smooth' });
    });
  }

  saveNewRow(elementToAppend, outputElement, URLNewRow, criteria, reload = false) {
    $.ajax({
      method: "POST",
      url: URLNewRow,
      data: criteria,
    }).done(function (response) {
        if (response.result === true) {
          outputElement.css('color', '#2ec551');
        }else {
          reload = false;
          outputElement.css('color', '#ef172c');
        }

        if(reload === false){
          outputElement.text(response.message);
          elementToAppend.html(response.HTMLData.content);
        }else{
          location.reload();
        }
    });
  }

  deleteNewRow(elementContainer){
    elementContainer.remove();
  }

  editRow(elementToReplace, URLNewRow, criteria) {
    //call controller that response the new row
    $.ajax({
      method: 'POST',
      url: URLNewRow,
      data: criteria
    }).done(function (response) {
      elementToReplace.replaceWith(response);
    });
  }

  saveEditRow(elementToAppend, outputElement, URLEditRow, criteria) {
    //call controller that response the new row
    $.ajax({
      method: "POST",
      url: URLEditRow,
      data: criteria,
    }).done(function (response) {
      if (response.result === true)
        outputElement.css('color', '#2ec551');
      else
        outputElement.css('color', '#ef172c');

      outputElement.text(response.message);
      elementToAppend.html(response.HTMLData.content);
    });
  }

  deleteRow(elementToDelete, outputElement, URLDeleteRow, criteria){
    $.ajax({
      method: "POST",
      url: URLDeleteRow,
      data: criteria,
    }).done(function (response) {
      if (response.result === true) {
        outputElement.css('color', '#2ec551');
        elementToDelete.remove();
      }else {
        outputElement.css('color', '#ef172c');
      }

      outputElement.text(response.message);
    });
  }

  returnTableData(elementToAppend,outputElement, URLBusinessTrafficDataMonth, criteria){
    $.ajax({
      method: "POST",
      url: URLBusinessTrafficDataMonth,
      data: criteria,
    }).done(function (response) {
      if(response.result === true){
        elementToAppend.html(response.HTMLData.content);
      }else{
        outputElement.css('color', '#ef172c');
        outputElement.text(response.message);
      }
    });
  }

  saveNewBudget(elementToAppend, outputElement, URLNewRow, criteria, reload = false) {
    $.ajax({
      method: "POST",
      url: URLNewRow,
      data: criteria,
    }).done(function (response) {
      if (response.result === true) {
        outputElement.css('color', '#2ec551');
        elementToAppend.append(response.HTMLData.content);
      }else {
        reload = false;
        outputElement.css('color', '#ef172c');
      }

      if(reload === false){
        outputElement.text(response.message);
      }else{
        location.reload();
      }
    });
  }

  activeBudget(starToActive, URLBudgetActive, criteria){
    $.ajax({
      method: "POST",
      url: URLBudgetActive,
      data: criteria,
    }).done(function (response) {
      let star = $('.star');
      if(response.result === true){
        star.removeClass('star-active');
        star.addClass('star-inactive');
        starToActive.removeClass('star-inactive');
        starToActive.addClass('star-active');
      }
    });
  }

  renameBudget(elementToRename, outputElement, URLRenameBudget, criteria){
    $.ajax({
      method: "POST",
      url: URLRenameBudget,
      data: criteria,
    }).done(function (response) {
      if (response.result === true) {
        outputElement.text('');
        elementToRename.attr('contenteditable', 'false');
        elementToRename.css('border', 'none');
      }else {
        outputElement.css('color', '#ef172c');
        outputElement.text(response.message);
      }
    });
  }

  deletebudget(elementToDelete, outputElement, URLDeleteBudget, criteria){
    $.ajax({
      method: "POST",
      url: URLDeleteBudget,
      data: criteria,
    }).done(function (response) {
      if (response.result === true) {
        outputElement.text('');
        elementToDelete.remove();
      }else {
        outputElement.css('color', '#ef172c');
        outputElement.text(response.message);
      }
    });
  }

  percentualSpent(elementToAppend, outputElement, URLPercentualSpent, criteria){
    //call controller that response the new row
    $.ajax({
      method: 'POST',
      url: URLPercentualSpent,
      data: criteria
    }).done(function (response) {
      if (response.result === true) {
        elementToAppend.html(response.HTMLData.content);
      }else {
        outputElement.css('color', '#ef172c');
        outputElement.text(response.message);
      }
    });
  }

}