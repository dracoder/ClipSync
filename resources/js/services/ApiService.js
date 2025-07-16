import axios from "axios";
import { ResponseWrapper } from "./ResponseWrapper.js";

class ApiService {
    // static axios object with default properties
    static axios = axios.create({
        // baseURL: import.meta.env.VITE_APP_URL,
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
        },
    });

    // static method to set the default 'Authorization' header
    static setToken(token) {
        ApiService.axios.defaults.headers.common[
            "Authorization"
        ] = `Bearer ${token}`;
    }

    // static method to remove the default 'Authorization' header
    static removeToken() {
        ApiService.axios.defaults.headers.common["Authorization"] = null;
    }

    //method to add specific headers
    static addHeaders(headers) {
        ApiService.axios.defaults.headers.common = headers;
        if (headers["Content-Type"]) {
            ApiService.axios.defaults.headers["Content-Type"] =
                headers["Content-Type"];
        }
        let locale = localStorage.getItem("lang") || "en";
        ApiService.axios.defaults.headers["X-Locale"] = locale;
    }

    //set route with slash

    static urlEncode = (data, prefix = "") => {
        let query = [];
        for (let key in data) {
            if (data.hasOwnProperty(key)) {
                let value = data[key];
                if (value === null || value === undefined) {
                    continue;
                }
                let enkey = prefix ? `${prefix}[${key}]` : key;
                if (typeof value === "object") {
                    query.push(this.urlEncode(value, enkey));
                } else {
                    query.push(encodeURIComponent(enkey) + "=" + encodeURIComponent(value));
                }
            }
        }
        if (query.length === 0) {
            return "";
        }
        query = query.reduce((acc, val) => {
            if(val !== ""){
                acc.push(val);
            }
            return acc;
        }, []);
        return query.join("&");
    }

    static setRoute = (resource, data = {}) => {
        if (!resource.startsWith("/")) {
            resource = resource.replace(/^/, "/");
        }
        if (resource.endsWith("/")) {
            resource = resource.slice(0, -1);
        }
        // prepare query string
        if (data && Object.keys(data).length > 0) {
            for (const [key, value] of Object.entries(data)) {
                if (value === null || value === undefined || value === "") {
                    delete data[key];
                }
            }
            let query = ApiService.urlEncode(data);
            if (query) {
                resource = `${resource}?${query}`;
            }
        }
        return resource;
    };

    // static method to get a resource allow to pass a custom headers
    static async get(resource, data, headers = null) {
        try {
            if (headers) {
                ApiService.addHeaders(headers);
            }
            resource = this.setRoute(resource, data);
            let response = await ApiService.axios.get(`${resource}`, data);
            if (!response.data.success) {
                return new ResponseWrapper(response);
            }

            return new ResponseWrapper(response);
        } catch (error) {
            return new ResponseWrapper(error, true);
        }
    }

    // static method to post a resource allow to pass a custom headers
    static async post(resource, data, headers = null) {
        try {
            if (headers) {
                ApiService.addHeaders(headers);
            }
            resource = this.setRoute(resource);
            let response = await ApiService.axios.post(`${resource}`, data);
            if (!response.data.success) {
                return new ResponseWrapper(response);
            }
            return new ResponseWrapper(response);
        } catch (error) {
            return new ResponseWrapper(error, true);
        }
    }

    // static method to update a resource allow to pass a custom headers
    static async put(resource, data, headers = null) {
        try {
            if (headers) {
                ApiService.addHeaders(headers);
            }
            resource = this.setRoute(resource);
            let response = await ApiService.axios.put(`${resource}`, data);
            if (!response.data.success) {
                return new ResponseWrapper(response);
            }
            return new ResponseWrapper(response);
        } catch (error) {
            return new ResponseWrapper(error, true);
        }
    }

    // static method to delete a resource allow to pass a custom headers
    static async delete(resource, headers = null) {
        try {
            if (headers) {
                ApiService.addHeaders(headers);
            }
            resource = this.setRoute(resource);
            let response = await ApiService.axios.delete(`${resource}`);
            if (!response.data.success) {
                return new ResponseWrapper(response);
            }
            return new ResponseWrapper(response);
        } catch (error) {
            return new ResponseWrapper(error, true);
        }
    }
}

export default ApiService;
