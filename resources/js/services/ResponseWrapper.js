function _getStatusMessage(status) {
    let message = "";
    switch (status) {
        case 200:
            message = "All done. Request successfully executed";
            break;
        case 201:
            message = "Data successfully created";
            break;
        case 400:
            message = "Bad Request";
            break;
        case 401:
            message = "Need auth";
            break;
        case 404:
            message = "Not found";
            break;
        case 503:
            message = "Service unavailable. Try again later";
            break;
        default:
            message = "Something wrong. Client default error message";
            break;
    }
    return message;
}

export class ResponseWrapper {
    constructor(response, isError = false) {
        if (isError) {
            this.errorResponse(response);
        } else {
            this.successResponse(response);
        }
    }
    successResponse(response) {
        this.success = response.data.success;
        this.data = response.data.data;
        this.status = response.status;
        this.statusMessage = _getStatusMessage(response.status);
        this.message = response.data.message;
        this.errors = response.data.errors;
    }

    errorResponse(error) {
        this.success=  false;
        this.data = null;
        this.status = error.response.status;
        this.statusMessage = _getStatusMessage(error.response.status);
        this.code = error.response.data.code;
        this.message = error.response.data.message;
        this.errors = error.response.data.errors;
    }
}
