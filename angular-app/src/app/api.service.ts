import { Injectable } from "@angular/core";
import { HttpClient, HttpHeaders } from "@angular/common/http";

@Injectable({
  providedIn: "root",
})
export class ApiService {
  protected defaultOptions = {
    headers: new HttpHeaders({
      "Content-Type": "application/json",
    }),
  };

  protected baseUrl = "api/v1/";

  constructor(private http: HttpClient) {}

  private extractData(res: Response) {
    let body = res;
    return body || {};
  }

  public get(path: string, options: object = {}): Promise<any> {
    return this.http
      .get(this.baseUrl + path, { ...this.defaultOptions, ...options })
      .toPromise();
  }

  public post(path: string, data: object, options: object = {}): Promise<any> {
    return this.http
      .post(this.baseUrl + path, data, { ...this.defaultOptions, ...options })
      .toPromise();
  }

  public patch(path: string, data: object, options: object = {}): Promise<any> {
    return this.http
      .patch(this.baseUrl + path, data, { ...this.defaultOptions, ...options })
      .toPromise();
  }
}
