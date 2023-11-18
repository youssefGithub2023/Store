// Start header dropdown
const dropdownBtns = Array.from(document.querySelectorAll(".dropdown-btn-header"));

dropdownBtns.forEach(function (dropdownBtn) {
    dropdownBtn.addEventListener("click", function () {
        this.querySelector("span").classList.toggle("active");
        this.querySelector("span i").classList.toggle("fa-chevron-down");
        this.querySelector("span i").classList.toggle("fa-chevron-up");
        this.nextElementSibling.classList.toggle("show");
    });
});

const allCatsChildDropdown = Array.from(document.querySelectorAll(".cats-child-dropdown"));

allCatsChildDropdown.forEach(function (dropdownChild) {
    dropdownChild.querySelector("span").addEventListener("click", function () {
        this.classList.toggle("active");
        this.querySelector("i").classList.toggle("fa-chevron-right");
        this.querySelector("i").classList.toggle("fa-chevron-left");
        dropdownChild.querySelector("ul").classList.toggle("show");
    });
});
// End header dropdown

// Start delete confirm
const deleteLinks = document.querySelectorAll("a.delete, .item-cart-del");

deleteLinks.forEach(function (item) {
    item.addEventListener("click", function (e) {
        let link = item.getAttribute("href"),
            del;

        if (link.includes("deleteComment.php")) {
            del = "this comment";
        } else if (link.includes("deleteItem.php?itemId")) {
            del = "this item";
        } else if (link.includes("block=delete&notId")) {
            del = "this notification";
        } else if (link.includes("block=deleteAll")) {
            del = "all notifications";
        } else if (link.includes("block=delete&disId")) {
            del = "this discount";
        } else if (link.includes("deleteItemFromCart.php?itId")) {
            del = "this item from cart";
        }
        
        let conf = confirm("Are you sure you wont to delete " + del);
        
        if (!conf) {
            e.preventDefault();
        }
    });
});
// End delete confirm

// Start show details of an item
const items = document.querySelectorAll(".item-box");
items.forEach(function (item) {
    item.addEventListener("click", function () {
        window.location.assign(this.dataset.location);
    });
});
// End show details of an item

// Start search items
const searchInput = document.getElementById("searchInput"),
    suggestionsItems = document.getElementById("suggestionsItems");

function removeAllChildNodes(parent) {
    while (parent.firstChild) {
        parent.removeChild(parent.firstChild);
    }
}

searchInput.addEventListener("input", function () {
    let inpVal = this.value;
    // Delete all previus suggestions
    removeAllChildNodes(suggestionsItems);

    if (/^[A-z0-9-+_\.,]+$/.test(inpVal)) {
        let myRequest = new XMLHttpRequest();

        myRequest.onreadystatechange = function () {
            if (this.readyState == 4 && this.status == 200) {
                let response = JSON.parse(this.responseText);

                for (let sugg of response) {
                    let option = document.createElement("option"),
                        attr = document.createAttribute("value");
                    attr.value = sugg;
                    option.setAttributeNode(attr);
                    suggestionsItems.appendChild(option);
                }
            }
        }

        myRequest.open("GET", "suggestionsNamesOfItems.xhr.php?inpVal=" + inpVal, true);
        myRequest.send();

    }

});
// End search items

// ======================================= + End main statments + =====================================

const href = location.href;

if (href.includes("notifications.php")) {
    // Start notification
    // = Go to the last of the page
    if (document.documentElement.contains(document.querySelector(".notifications"))) {
        const htmlEle = document.documentElement,
            scrollHeight = htmlEle.scrollHeight,
            clientHeight = htmlEle.clientHeight,
            hideArea = scrollHeight - clientHeight;

        htmlEle.scrollTop = hideArea;
    }

    // Delete a notification by ajax
    const deleteNotEles = document.querySelectorAll("span.not-delete"),
        notsContainer = document.querySelector(".notifications .container");

    deleteNotEles.forEach(function (deleteNotEle) {
        deleteNotEle.addEventListener("click", function () {
            let DeleteNotRequest = new XMLHttpRequest();

            DeleteNotRequest.onreadystatechange = function () {
                if (this.readyState == 4 && this.status == 200) {
                    let res = this.responseText;

                    if (res == "1") {

                        deleteNotEle.parentElement.classList.add("deleteAnim");

                        setTimeout(function () {
                            deleteNotEle.parentElement.remove();

                            if (! notsContainer.contains(document.querySelector(".container .notification"))) {
                                removeAllChildNodes(notsContainer);
    
                                const wor = document.createElement("section"),
                                    cAttr = document.createAttribute("class"),
                                    text = document.createTextNode("There is no notifications to show");
    
                                cAttr.value = "worning";
                                wor.setAttributeNode(cAttr);
                                wor.appendChild(text);
    
                                notsContainer.appendChild(wor);
                            }
                        }, 250);
                        
                    } else {
                        alert("The notification not deleted");
                    }
                }
            }

            DeleteNotRequest.open("GET", "deleteNotifications.xhr.php?block=delete&notId=" + this.dataset.notid, true);
            DeleteNotRequest.send();
        });
    });

    // Delete all notifications by ajax
    const deleteAllNotsBtn = document.getElementById("deleteAllNots");
    if (deleteAllNotsBtn) {
        deleteAllNotsBtn.addEventListener("click", function () {
            let deleteAllNotsRequest = new XMLHttpRequest();
            deleteAllNotsRequest.onreadystatechange = function () {
                if (this.readyState == 4 && this.status == 200) {
                    let resDA = this.responseText;
    
                    if (resDA == "1") {

                        let getAllNots = notsContainer.querySelectorAll(".notification"),
                            c = 0;

                        let myInt = setInterval(function () {

                            getAllNots[c].remove()
                            c++;

                            if (c == getAllNots.length) {

                                deleteAllNotsBtn.remove();

                                const wor = document.createElement("section"),
                                    cAttr = document.createAttribute("class"),
                                    text = document.createTextNode("There is no notifications to show");

                                cAttr.value = "worning";
                                wor.setAttributeNode(cAttr);
                                wor.appendChild(text);

                                notsContainer.appendChild(wor);

                                clearInterval(myInt);
                            }
                        }, 100);

                    } else {
                        alert("The notifications not deleted");
                    }
                }
            }
    
            deleteAllNotsRequest.open("GET", "deleteNotifications.xhr.php?block=deleteAll", true);
            deleteAllNotsRequest.send();
        });
    }
    // End notification
} else if (href.includes("cart.php")) {
    // Start cart page
    const checkoutBtn = document.getElementById("checkout"),
        overlay = document.getElementById("cartOverlay"),
        cancelBtn = document.getElementById("overlayCancel");
    checkoutBtn.addEventListener("click", function () {
        overlay.classList.add("show-overlay");
        setTimeout(function () {
            overlay.classList.add("animate");
        }, 1);
    });
    cancelBtn.addEventListener("click", function () {
        overlay.classList.remove("show-overlay", "animate");
    });
    // End cart page
} else if (href.includes("sales.php") || href.includes("purchases.php")) {
    // Start sales and purchases page
    const receivedBtns = Array.from(document.getElementsByClassName("received")),
        purOverlay = document.getElementById("cartOverlay"),
        inputCartId = document.getElementById("cartId"),
        inputItemId = document.getElementById("itemId"),
        textarea = document.getElementById("comment"),
        likeBtn = document.getElementById("like"),
        dislikeBtn = document.getElementById("dislike"),
        purCancelBtn = document.getElementById("overlayCancel"),
        okBtn = document.getElementById("ok");

    receivedBtns.forEach(function (receivedBtn) {
    receivedBtn.addEventListener("click", function () {
        inputCartId.value = this.dataset.cid;
        inputItemId.value = this.dataset.itid;
        purOverlay.classList.add("show-overlay");
        setTimeout(function () {
            purOverlay.classList.add("animate");
        }, 1);
    });
    });

    purCancelBtn.addEventListener("click", function () {
        purOverlay.classList.remove("show-overlay", "animate");
        textarea.value = "";
        likeBtn.classList.remove("active");
        dislikeBtn.classList.remove("active");
    });

    likeBtn.addEventListener("click", function () {
        this.classList.toggle("active");
        dislikeBtn.classList.remove("active");
    });
    dislikeBtn.addEventListener("click", function () {
        this.classList.toggle("active");
        likeBtn.classList.remove("active");
    });

    okBtn.addEventListener("click", function () {
    // Generate get request
    let cartId = inputCartId.value,
        itemId = inputItemId.value,
        commentValue = textarea.value,
        rat = "";

    if (likeBtn.classList.contains("active")) {
        rat = "l";
    } else if (dislikeBtn.classList.contains("active")) {
        rat = "d";
    }

    let get = "received.php?cId=" + cartId + "&itId=" + itemId + "&comment=" + commentValue + "&rat=" + rat;

    window.location.assign(get);
    });

    const dropdownBtn = document.getElementById("dropdown-btn"),
        dropdownItems = document.getElementById("dropdown-items");
    dropdownBtn.addEventListener("click", function () {
        this.classList.toggle("active");
        dropdownItems.classList.toggle("show");
    });

    const shosDetailsBtns = Array.from(document.querySelectorAll(".show-details-btn"));
        shosDetailsBtns.forEach(function (sBtn) {
        sBtn.addEventListener("click", function () {
            this.parentElement.nextElementSibling.classList.toggle("show");
        });
    });
    // End sales and purchases page
} else if (href.includes("profile.php")) {
    // Start profile page
    const editProfileImgBtn = document.getElementById("editProfileImg"),
        profileOverlay = document.getElementById("profileOverlay"),
        cancelOvBtn = document.getElementById("cancelOverlay");

    editProfileImgBtn.addEventListener("click", function () {
        profileOverlay.classList.add("show-overlay");
        setTimeout(function () {
            profileOverlay.classList.add("animate");
        }, 1);
    });

    cancelOvBtn.addEventListener("click", function () {
        profileOverlay.classList.remove("show-overlay", "animate");
    });
    // End profile page
} else if (href.includes("itemDetails.php")) {
    // Start itemDetails page
    const editImgBtns = document.querySelectorAll(".editImgBtn"),
        iDOverlay = document.getElementById("itemOverlay"),
        cancelIDOverlay = document.getElementById("cancelOverlay"),
        label = document.getElementById("labelText"),
        colName = document.getElementById("col"),
        deleteBtn = document.getElementById("delete");

        editImgBtns.forEach(function (btn) {
        btn.addEventListener("click", function () {
            iDOverlay.classList.add("show-overlay");
            setTimeout(function () {
                iDOverlay.classList.add("animate");
            }, 1);

            label.textContent = btn.dataset.head;
            colName.value = btn.dataset.col;

            if (btn.dataset.col == "itemImg") {
                deleteBtn.style.display = "none";
            } else {
                deleteBtn.style.display = "block";
            }
        });
    });

    cancelIDOverlay.addEventListener("click", function () {
        iDOverlay.classList.remove("show-overlay", "animate");
    });
    // Start itemDetails page
}