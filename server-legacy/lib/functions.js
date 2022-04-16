var isNumber = function (o) {
    return ! isNaN (o-0) && o !== null && o !== "" && o !== false;
}