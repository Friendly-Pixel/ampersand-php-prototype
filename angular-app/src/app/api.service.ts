import { Injectable } from "@angular/core";
import { HttpClient, HttpHeaders } from "@angular/common/http";

@Injectable({
  providedIn: "root",
})
export class ApiService {
  private defaultOptions = {
    headers: new HttpHeaders({
      "Content-Type": "application/json",
    }),
  };

  private baseUrl = "api/v1";

  constructor(private http: HttpClient) {}

  private extractData(res: Response) {
    let body = res;
    return body || {};
  }

  public get(path: string): Promise<any> {
    let promise = new Promise((resolve, reject) => {
      this.http
        .get(this.baseUrl + "/" + path, this.defaultOptions)
        .toPromise()
        .then((res) => {
          // Success
          console.log(res);
          resolve(res);
        });
    });
    return promise;
  }

  // TODO: implement
  public post(path: string, data: object): Promise<any> {
    return Promise.resolve();
  }

  public async patch(path: string, data: object): Promise<any> {
    return new Promise((resolve, reject) => {
      this.http
        .patch(this.baseUrl + "/" + path, data, this.defaultOptions)
        .toPromise()
        .then((res) => {
          // Success
          console.log(res);
          resolve(res);
        });
    });
  }
}
