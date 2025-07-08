/**
 * SPK Beasiswa Prestasi - Custom JavaScript
 * SMP Negeri 2 Ampek Angkek
 */

(function () {
  "use strict";

  // DOM Ready Functions
  document.addEventListener("DOMContentLoaded", function () {
    initializeApp();
  });

  /**
   * Initialize Application
   */
  function initializeApp() {
    setupAlerts();
    setupFormValidation();
    setupTableEnhancements();
    setupTooltips();
    setupNumberFormatting();
    setupSearchFunctionality();
    setupLoadingStates();
    setupAnimations();
    setupAccessibility();
  }

  /**
   * Alert Management
   */
  function setupAlerts() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll(".alert:not(.alert-permanent)");
    alerts.forEach(function (alert) {
      setTimeout(function () {
        if (alert && alert.parentNode) {
          const bsAlert = new bootstrap.Alert(alert);
          if (bsAlert) {
            bsAlert.close();
          }
        }
      }, 5000);
    });

    // Add fade out animation to alerts
    alerts.forEach(function (alert) {
      alert.classList.add("fade-in");
    });
  }

  /**
   * Form Validation Enhancement
   */
  function setupFormValidation() {
    const forms = document.querySelectorAll("form[novalidate]");

    forms.forEach(function (form) {
      form.addEventListener(
        "submit",
        function (e) {
          if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();

            // Focus on first invalid field
            const firstInvalid = form.querySelector(":invalid");
            if (firstInvalid) {
              firstInvalid.focus();
              firstInvalid.scrollIntoView({
                behavior: "smooth",
                block: "center",
              });
            }
          }
          form.classList.add("was-validated");
        },
        false
      );

      // Real-time validation
      const inputs = form.querySelectorAll("input, select, textarea");
      inputs.forEach(function (input) {
        input.addEventListener("input", function () {
          if (input.checkValidity()) {
            input.classList.remove("is-invalid");
            input.classList.add("is-valid");
          } else {
            input.classList.remove("is-valid");
            input.classList.add("is-invalid");
          }
        });
      });
    });
  }

  /**
   * Table Enhancements
   */
  function setupTableEnhancements() {
    // Row hover effects
    const tableRows = document.querySelectorAll(".table tbody tr");
    tableRows.forEach(function (row) {
      row.addEventListener("mouseenter", function () {
        this.style.transform = "scale(1.01)";
        this.style.boxShadow = "0 4px 8px rgba(0,0,0,0.1)";
      });

      row.addEventListener("mouseleave", function () {
        this.style.transform = "scale(1)";
        this.style.boxShadow = "none";
      });
    });

    // Sortable tables
    setupTableSorting();
  }

  /**
   * Table Sorting Functionality
   */
  function setupTableSorting() {
    const sortableHeaders = document.querySelectorAll(
      ".table th[data-sortable]"
    );

    sortableHeaders.forEach(function (header) {
      header.style.cursor = "pointer";
      header.innerHTML += ' <i class="bi bi-arrow-down-up text-muted"></i>';

      header.addEventListener("click", function () {
        const table = this.closest("table");
        const tbody = table.querySelector("tbody");
        const rows = Array.from(tbody.querySelectorAll("tr"));
        const columnIndex = Array.from(this.parentNode.children).indexOf(this);
        const isAscending = this.classList.contains("asc");

        // Remove previous sort indicators
        sortableHeaders.forEach((h) => {
          h.classList.remove("asc", "desc");
          const icon = h.querySelector("i");
          if (icon) {
            icon.className = "bi bi-arrow-down-up text-muted";
          }
        });

        // Sort rows
        rows.sort(function (a, b) {
          const aVal = a.children[columnIndex].textContent.trim();
          const bVal = b.children[columnIndex].textContent.trim();

          // Check if values are numbers
          const aNum = parseFloat(aVal.replace(/[^0-9.-]/g, ""));
          const bNum = parseFloat(bVal.replace(/[^0-9.-]/g, ""));

          if (!isNaN(aNum) && !isNaN(bNum)) {
            return isAscending ? bNum - aNum : aNum - bNum;
          } else {
            return isAscending
              ? bVal.localeCompare(aVal)
              : aVal.localeCompare(bVal);
          }
        });

        // Update DOM
        rows.forEach((row) => tbody.appendChild(row));

        // Update sort indicator
        this.classList.add(isAscending ? "desc" : "asc");
        const icon = this.querySelector("i");
        if (icon) {
          icon.className = isAscending ? "bi bi-arrow-down" : "bi bi-arrow-up";
        }
      });
    });
  }

  /**
   * Tooltip Setup
   */
  function setupTooltips() {
    const tooltipTriggerList = [].slice.call(
      document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  }

  /**
   * Number Formatting
   */
  function setupNumberFormatting() {
    // Format number inputs
    const numberInputs = document.querySelectorAll('input[type="number"]');
    numberInputs.forEach(function (input) {
      input.addEventListener("input", function () {
        formatNumberInput(this);
      });
    });

    // Format currency displays
    const currencyElements = document.querySelectorAll(".currency");
    currencyElements.forEach(function (element) {
      const value = parseFloat(element.textContent);
      if (!isNaN(value)) {
        element.textContent = formatCurrency(value);
      }
    });
  }

  /**
   * Search Functionality
   */
  function setupSearchFunctionality() {
    const searchInputs = document.querySelectorAll("input[data-search]");

    searchInputs.forEach(function (input) {
      const targetSelector = input.dataset.search;
      const targetElements = document.querySelectorAll(targetSelector);

      input.addEventListener("input", function () {
        const searchTerm = this.value.toLowerCase();

        targetElements.forEach(function (element) {
          const text = element.textContent.toLowerCase();
          const shouldShow = text.includes(searchTerm);

          element.style.display = shouldShow ? "" : "none";

          // Add highlight to matching text
          if (shouldShow && searchTerm) {
            highlightText(element, searchTerm);
          } else {
            removeHighlight(element);
          }
        });
      });
    });
  }

  /**
   * Loading States
   */
  function setupLoadingStates() {
    const forms = document.querySelectorAll("form");

    forms.forEach(function (form) {
      form.addEventListener("submit", function () {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
          const originalText = submitBtn.innerHTML;
          submitBtn.innerHTML =
            '<i class="bi bi-hourglass-split"></i> Memproses...';
          submitBtn.disabled = true;

          // Re-enable after 10 seconds (fallback)
          setTimeout(function () {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
          }, 10000);
        }
      });
    });

    // Loading overlay for AJAX requests
    window.showLoading = function () {
      const overlay = document.createElement("div");
      overlay.id = "loading-overlay";
      overlay.innerHTML = `
                <div class="d-flex justify-content-center align-items-center h-100">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div class="mt-2">Memproses...</div>
                    </div>
                </div>
            `;
      overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255,255,255,0.8);
                z-index: 9999;
                backdrop-filter: blur(2px);
            `;
      document.body.appendChild(overlay);
    };

    window.hideLoading = function () {
      const overlay = document.getElementById("loading-overlay");
      if (overlay) {
        overlay.remove();
      }
    };
  }

  /**
   * Animations
   */
  function setupAnimations() {
    // Intersection Observer for scroll animations
    const observerOptions = {
      threshold: 0.1,
      rootMargin: "0px 0px -50px 0px",
    };

    const observer = new IntersectionObserver(function (entries) {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("fade-in");
        }
      });
    }, observerOptions);

    // Observe cards and important elements
    const animatableElements = document.querySelectorAll(
      ".card, .alert, .table"
    );
    animatableElements.forEach((el) => observer.observe(el));
  }

  /**
   * Accessibility Enhancements
   */
  function setupAccessibility() {
    // Keyboard navigation for custom elements
    const focusableElements = document.querySelectorAll(
      "[tabindex], button, input, select, textarea, a[href]"
    );

    focusableElements.forEach(function (element) {
      element.addEventListener("keydown", function (e) {
        // Escape key handling
        if (e.key === "Escape") {
          if (element.tagName === "INPUT" || element.tagName === "TEXTAREA") {
            element.blur();
          }
        }
      });
    });

    // Screen reader announcements
    window.announceToScreenReader = function (message) {
      const announcement = document.createElement("div");
      announcement.setAttribute("aria-live", "polite");
      announcement.setAttribute("aria-atomic", "true");
      announcement.className = "sr-only";
      announcement.textContent = message;
      document.body.appendChild(announcement);

      setTimeout(() => {
        document.body.removeChild(announcement);
      }, 1000);
    };
  }

  /**
   * Utility Functions
   */

  // Confirm delete with custom styling
  window.confirmDelete = function (
    message = "Apakah Anda yakin ingin menghapus data ini?"
  ) {
    return new Promise((resolve) => {
      const modal = document.createElement("div");
      modal.className = "modal fade";
      modal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">
                                <i class="bi bi-exclamation-triangle"></i> Konfirmasi Hapus
                            </h5>
                        </div>
                        <div class="modal-body">
                            <p>${message}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="button" class="btn btn-danger" id="confirm-delete-btn">Hapus</button>
                        </div>
                    </div>
                </div>
            `;

      document.body.appendChild(modal);
      const bsModal = new bootstrap.Modal(modal);
      bsModal.show();

      modal
        .querySelector("#confirm-delete-btn")
        .addEventListener("click", () => {
          bsModal.hide();
          resolve(true);
        });

      modal.addEventListener("hidden.bs.modal", () => {
        document.body.removeChild(modal);
        resolve(false);
      });
    });
  };

  // Format number input
  function formatNumberInput(input) {
    let value = input.value.replace(/[^\d.,]/g, "");
    value = value.replace(",", ".");

    // Limit decimal places
    if (input.step) {
      const decimals = input.step.split(".")[1]?.length || 0;
      if (decimals > 0) {
        const parts = value.split(".");
        if (parts[1] && parts[1].length > decimals) {
          parts[1] = parts[1].substring(0, decimals);
          value = parts.join(".");
        }
      }
    }

    input.value = value;
  }

  // Format currency
  function formatCurrency(amount) {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      minimumFractionDigits: 0,
    }).format(amount);
  }

  // Highlight text in search results
  function highlightText(element, searchTerm) {
    removeHighlight(element);

    const text = element.textContent;
    const regex = new RegExp(`(${searchTerm})`, "gi");
    const highlightedText = text.replace(regex, "<mark>$1</mark>");

    if (text !== highlightedText) {
      element.innerHTML = highlightedText;
    }
  }

  // Remove text highlighting
  function removeHighlight(element) {
    const marks = element.querySelectorAll("mark");
    marks.forEach((mark) => {
      mark.outerHTML = mark.textContent;
    });
  }

  // Copy to clipboard
  window.copyToClipboard = function (text) {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(text).then(() => {
        showToast("Berhasil disalin ke clipboard", "success");
      });
    } else {
      // Fallback for older browsers
      const textArea = document.createElement("textarea");
      textArea.value = text;
      document.body.appendChild(textArea);
      textArea.select();
      document.execCommand("copy");
      document.body.removeChild(textArea);
      showToast("Berhasil disalin ke clipboard", "success");
    }
  };

  // Show toast notification
  window.showToast = function (message, type = "info") {
    const toast = document.createElement("div");
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

    let toastContainer = document.querySelector(".toast-container");
    if (!toastContainer) {
      toastContainer = document.createElement("div");
      toastContainer.className =
        "toast-container position-fixed bottom-0 end-0 p-3";
      document.body.appendChild(toastContainer);
    }

    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();

    toast.addEventListener("hidden.bs.toast", () => {
      toastContainer.removeChild(toast);
    });
  };

  // Print function
  window.printPage = function () {
    window.print();
  };

  // Export table to CSV
  window.exportTableToCSV = function (tableSelector, filename = "data.csv") {
    const table = document.querySelector(tableSelector);
    if (!table) return;

    const rows = table.querySelectorAll("tr");
    const csv = Array.from(rows)
      .map((row) => {
        const cells = row.querySelectorAll("th, td");
        return Array.from(cells)
          .map((cell) => '"' + cell.textContent.replace(/"/g, '""') + '"')
          .join(",");
      })
      .join("\n");

    const blob = new Blob([csv], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
  };

  // Lazy loading for images
  if ("IntersectionObserver" in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.src = img.dataset.src;
          img.classList.remove("lazy");
          imageObserver.unobserve(img);
        }
      });
    });

    document.querySelectorAll("img[data-src]").forEach((img) => {
      imageObserver.observe(img);
    });
  }

  // Performance monitoring
  if ("performance" in window) {
    window.addEventListener("load", function () {
      const loadTime =
        performance.timing.loadEventEnd - performance.timing.navigationStart;
      console.log(`Page loaded in ${loadTime}ms`);
    });
  }
})();
