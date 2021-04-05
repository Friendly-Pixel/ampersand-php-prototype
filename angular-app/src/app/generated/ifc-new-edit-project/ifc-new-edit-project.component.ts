import { Component, OnInit } from "@angular/core";
import { ActivatedRoute } from "@angular/router";
import { ApiService } from "src/app/api.service";

@Component({
  selector: "app-ifc-new-edit-project",
  templateUrl: "./ifc-new-edit-project.component.html",
  styleUrls: ["./ifc-new-edit-project.component.css"],
})
export class IfcNewEditProjectComponent implements OnInit {
  public data: any;
  constructor(protected route: ActivatedRoute, protected api: ApiService) {}

  ngOnInit(): void {
    const routeParams = this.route.snapshot.paramMap;
    const projectId = routeParams.get("projectId");
    this.api
      .get(`resource/Project/${projectId}/New_47_edit_32_project`)
      .then((data) => {
        this.data = data;
      });
  }
}
