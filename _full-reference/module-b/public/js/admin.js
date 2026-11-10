/*
 | Дополнительные скрипты админ-панели «Кухни мира».
 | Динамическое добавление строк ингредиентов и шагов в форме рецепта.
 */
(function () {
    "use strict";

    // Счётчики строк, которые никогда не уменьшаются — исключают совпадение индексов.
    var ingredientCounter = document.querySelectorAll("#ingredients-container .ingredient-row").length;
    var stepCounter = document.querySelectorAll("#steps-container .step-row").length;

    /**
     * Добавляет строку ингредиента на основе скрытого шаблона.
     */
    function addIngredientRow() {
        var template = document.getElementById("ingredient-row-template");
        var container = document.getElementById("ingredients-container");
        if (!template || !container) { return; }

        var index = ingredientCounter++;
        var html = template.innerHTML.replace(/__INDEX__/g, index);

        var wrapper = document.createElement("div");
        wrapper.className = "row g-2 align-items-end ingredient-row mb-2";
        wrapper.innerHTML = html;
        container.appendChild(wrapper);
    }

    /**
     * Добавляет строку шага.
     */
    function addStepRow() {
        var template = document.getElementById("step-row-template");
        var container = document.getElementById("steps-container");
        if (!template || !container) { return; }

        var index = stepCounter++;
        var html = template.innerHTML.replace(/__INDEX__/g, index);

        var wrapper = document.createElement("div");
        wrapper.className = "border rounded p-3 mb-2 step-row";
        wrapper.innerHTML = html;
        container.appendChild(wrapper);
    }

    /**
     * Удаляет строку (индикатор или шаг).
     */
    function removeRow(button) {
        var row = button.closest(".ingredient-row") || button.closest(".step-row");
        if (row) {
            row.parentNode.removeChild(row);
        }
    }

    document.addEventListener("click", function (event) {
        var addIngredient = event.target.closest("[data-add-ingredient]");
        if (addIngredient) {
            event.preventDefault();
            addIngredientRow();
            return;
        }

        var addStep = event.target.closest("[data-add-step]");
        if (addStep) {
            event.preventDefault();
            addStepRow();
            return;
        }

        var remove = event.target.closest("[data-remove-row]");
        if (remove) {
            event.preventDefault();
            removeRow(remove);
        }
    });
})();
