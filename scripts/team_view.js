// Get URL parameters
const urlParams = new URLSearchParams(window.location.search);
const teamId = urlParams.get("id");
const subjectId = urlParams.get("subject");

// Initialize page
document.addEventListener("DOMContentLoaded", () => {
  // Handle navigation
  const navSections = document.querySelectorAll(".nav-section");
  const contentSections = {
    "Team Details": document.querySelector(".team-details"),
    "Team Members": document.querySelector(".team-members"),
    "Project Details": document.querySelector(".project-details"),
    "Project Progress": document.querySelector(".project-progress"),
    "My Tasks": document.querySelector(".my-tasks"),
    "Shared Files": document.querySelector(".shared-files"),
  };

  navSections.forEach((section) => {
    section.addEventListener("click", () => {
      const sectionName = section.textContent.trim();

      // Update navigation active state
      navSections.forEach((s) => s.classList.remove("active"));
      section.classList.add("active");

      // Show/hide content sections
      Object.entries(contentSections).forEach(([name, element]) => {
        if (element) {
          element.style.display = name === sectionName ? "block" : "none";
        }
      });
    });
  });

  // Project Details Form Handling
  const projectForm = document.getElementById("project-form");
  const techInput = document.querySelector(".tech-input");
  const techTags = document.querySelector(".tech-tags");
  const addGoalBtn = document.querySelector(".add-goal-btn");
  const goalsList = document.querySelector(".goals-list");

  // Handle technology input
  techInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      const tech = techInput.value.trim();
      if (tech) {
        const tag = document.createElement("span");
        tag.className = "tech-tag";
        tag.dataset.tech = tech;
        tag.innerHTML = `${tech}<i class="ri-close-line remove-tech"></i>`;
        techTags.appendChild(tag);
        techInput.value = "";
      }
    }
  });

  // Remove technology tag
  techTags.addEventListener("click", (e) => {
    if (e.target.classList.contains("remove-tech")) {
      e.target.parentElement.remove();
    }
  });

  // Add new goal
  addGoalBtn.addEventListener("click", () => {
    const goalItem = document.createElement("div");
    goalItem.className = "goal-item";
    goalItem.innerHTML = `
                    <input type="text" required>
                    <i class="ri-delete-bin-line remove-goal"></i>
                `;
    goalsList.appendChild(goalItem);
  });

  // Remove goal
  goalsList.addEventListener("click", (e) => {
    if (e.target.classList.contains("remove-goal")) {
      e.target.parentElement.remove();
    }
  });

  // Handle form submission
  projectForm.addEventListener("submit", (e) => {
    e.preventDefault();

    // Collect form data
    const formData = {
      topic: projectForm.querySelector(".project-title-input").value,
      description: projectForm.querySelector(".project-description-input")
        .value,
      technologies: Array.from(techTags.children).map(
        (tag) => tag.dataset.tech
      ),
      timeline: {
        startDate: projectForm.querySelector(
          '.timeline-input[type="date"]:first-of-type'
        ).value,
        endDate: projectForm.querySelector(
          '.timeline-input[type="date"]:last-of-type'
        ).value,
      },
      goals: Array.from(goalsList.children).map(
        (goal) => goal.querySelector("input").value
      ),
    };

    // TODO: Send to backend
    console.log("Saving project details:", formData);

    // Show success message
    const saveBtn = projectForm.querySelector(".save-btn");
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="ri-check-line"></i> Saved!';
    saveBtn.classList.add("saved");

    setTimeout(() => {
      saveBtn.innerHTML = originalText;
      saveBtn.classList.remove("saved");
    }, 2000);
  });

  // Initialize Project Progress Handling
  const progressForm = document.getElementById("progress-form");
  const phasesList = document.querySelector(".phases-list");
  const addPhaseBtn = document.querySelector(".add-phase-btn");

  if (progressForm && phasesList && addPhaseBtn) {
    // Add new phase
    addPhaseBtn.addEventListener("click", () => {
      const phaseCount = phasesList.children.length + 1;
      const phaseItem = document.createElement("div");
      phaseItem.className = "phase-item";
      phaseItem.innerHTML = `
                        <div class="phase-content">
                            <input type="text" class="phase-name" value="${phaseCount}. New Phase" required>
                            <button type="button" class="phase-status pending">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                    `;
      phasesList.appendChild(phaseItem);
      updateOverallProgress();
    });
  }

  // Shared Files functionality
  const uploadBtn = document.getElementById("uploadBtn");
  const fileInput = document.getElementById("fileInput");
  const filesGrid = document.getElementById("filesGrid");

  uploadBtn.addEventListener("click", () => {
    fileInput.click();
  });

  fileInput.addEventListener("change", (e) => {
    const files = e.target.files;
    for (let file of files) {
      addFileCard(file);
    }
  });

  // Add file card to the grid
  function addFileCard(file) {
    const fileCard = document.createElement("div");
    fileCard.className = "file-card";

    const fileIcon = getFileIcon(file.type);
    const fileSize = formatFileSize(file.size);
    const uploadDate = new Date().toLocaleDateString();

    fileCard.innerHTML = `
                    <div class="file-icon">
                        <i class="ri-${fileIcon}"></i>
                    </div>
                    <div class="file-info">
                        <h3 class="file-name">${file.name}</h3>
                        <p class="file-meta">Uploaded by Current User</p>
                        <p class="file-meta">Just now • ${fileSize}</p>
                    </div>
                    <div class="file-actions">
                        <button class="action-btn" title="Download">
                            <i class="ri-download-line"></i>
                        </button>
                        <button class="action-btn delete-btn" title="Delete">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                `;

    // Add event listeners for actions
    const deleteBtn = fileCard.querySelector(".delete-btn");
    deleteBtn.addEventListener("click", () => {
      fileCard.remove();
    });

    filesGrid.insertBefore(fileCard, filesGrid.firstChild);
  }

  // Helper function to get appropriate icon based on file type
  function getFileIcon(fileType) {
    if (fileType.startsWith("image/")) return "image-line";
    if (fileType.includes("pdf")) return "file-pdf-line";
    if (fileType.includes("word")) return "file-word-line";
    if (fileType.includes("excel")) return "file-excel-line";
    return "file-text-line";
  }

  // Helper function to format file size
  function formatFileSize(bytes) {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + " " + sizes[i];
  }

  // Add delete functionality to existing sample cards
  document.querySelectorAll(".delete-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      btn.closest(".file-card").remove();
    });
  });

  // My Tasks Functionality
  const tasksForm = document.getElementById("tasks-form");
  const tasksList = document.querySelector(".tasks-list");

  if (tasksForm && tasksList) {
    // Toggle task details
    document.querySelectorAll(".task-toggle").forEach((toggle) => {
      toggle.addEventListener("click", () => {
        const taskHeader = toggle.closest(".task-header");
        const isExpanded = taskHeader.getAttribute("data-expanded") === "true";
        taskHeader.setAttribute("data-expanded", !isExpanded);
        toggle.style.transform = isExpanded ? "rotate(0deg)" : "rotate(180deg)";
      });
    });

    // Handle form submission
    tasksForm.addEventListener("submit", (e) => {
      e.preventDefault();

      // Collect form data
      const formData = {
        tasks: Array.from(tasksList.children).map((task) => ({
          name: task.querySelector(".task-name").textContent,
          description:
            task.querySelector(".task-description")?.textContent || "",
          status: task.querySelector('input[type="radio"]:checked').value,
        })),
      };

      // TODO: Send to backend
      console.log("Saving tasks:", formData);

      // Show success message
      const saveBtn = tasksForm.querySelector(".save-btn");
      const originalText = saveBtn.innerHTML;
      saveBtn.innerHTML = '<i class="ri-check-line"></i> Saved!';
      saveBtn.classList.add("saved");

      setTimeout(() => {
        saveBtn.innerHTML = originalText;
        saveBtn.classList.remove("saved");
      }, 2000);
    });
  }
});

document.querySelector(".add-phase-btn")?.addEventListener("click", () => {
  const container = document.querySelector(".phases-list");
  const index = container.querySelectorAll(".phase-item").length;

  const newPhase = document.createElement("div");
  newPhase.className = "phase-item";
  newPhase.innerHTML = `
        <div class="phase-content">
            <input type="text" class="phase-name" name="phases[${index}][name]" value="" required>
            <input type="hidden" class="phase-status-value" name="phases[${index}][is_completed]" value="0">
            <button type="button" class="phase-status pending">
                <i class="ri-close-line"></i>
            </button>
        </div>`;
  container.appendChild(newPhase);

  updateProgress(); // Update progress after adding a new phase
});

document.addEventListener("click", function (e) {
  if (e.target.closest(".phase-status")) {
    const btn = e.target.closest(".phase-status");
    const isNowCompleted = btn.classList.toggle("completed");
    btn.classList.toggle("pending");
    btn.innerHTML = `<i class="ri-${
      isNowCompleted ? "check" : "close"
    }-line"></i>`;

    // Update corresponding hidden input value
    const hiddenInput = btn.parentElement.querySelector(".phase-status-value");
    if (hiddenInput) {
      hiddenInput.value = isNowCompleted ? "1" : "0";
    }

    // Update overall progress
    updateProgress();
  }
});

function updateProgress() {
  const statusInputs = document.querySelectorAll(".phase-status-value");
  if (statusInputs.length === 0) return;

  const completedCount = Array.from(statusInputs).filter(
    (input) => input.value === "1"
  ).length;
  const total = statusInputs.length;
  const progress = Math.round((completedCount / total) * 100);

  // Update progress bar and percentage
  document.querySelector(".progress-fill").style.width = progress + "%";

  document.querySelector(".progress-percentage").textContent = `${progress}%`;
}
