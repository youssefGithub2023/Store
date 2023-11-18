// Start dropdown
const dropdownBtn = document.getElementById("dropdown-btn"),
    dropdown = document.getElementById("dropdown");

dropdownBtn.addEventListener("click", function () {
    dropdown.classList.toggle("show");
    this.querySelector("i").classList.toggle("active");
});
// End dropdown

// Start dashboard
const stats = document.querySelectorAll(".stats .stat");
stats.forEach(function (stat) {
    stat.addEventListener("click", function () {
        window.location.assign(this.dataset.location);
    });
});

const latestBoxes = document.querySelectorAll(".latest-box");
latestBoxes.forEach(function (latestBox) {
    let btn = latestBox.querySelector(".fade");
    btn.addEventListener("click", function () {
        btn.classList.toggle("fa-chevron-up");
        btn.classList.toggle("fa-chevron-down");
        latestBox.querySelector("ul").classList.toggle("fadein");
    })
})
// End dashboard

// Start delete confirm
const deleteLinks = document.querySelectorAll("a.delete");

deleteLinks.forEach(function (item) {
    item.addEventListener("click", function (e) {
        let link = item.getAttribute("href"),
            del;

        if (link.includes("members.php")) {
            del = "member";
        } else if (link.includes("categories.php")) {
            del = "category";
        } else if (link.includes("items.php")) {
            del = "item";
        } else if (link.includes("comments.php")) {
            del = "comment";
        }
        
        let conf = confirm("Are you sure you wont to delete this " + del + " has 'ID=" + link.slice(link.lastIndexOf("=") + 1) + "'");
        
        if (!conf) {
            e.preventDefault();
        }
    });
});
// End delete confirm

// Start statistics page
const filterCatsForm = document.querySelector("#filter-cats");
filterCatsForm.addEventListener("submit", function (e) {
    if (filterCatsForm.children[0].value == "" && filterCatsForm.children[1].value == "" && filterCatsForm.children[2].value == "") {
        e.preventDefault();
    }
});
// End statistics page
