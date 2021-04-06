import { Component, OnInit } from "@angular/core";
import { ActivatedRoute } from "@angular/router";
import { ApiService } from "src/app/api.service";
import { Resource } from "src/app/templates/models/resource.class";

@Component({
  selector: "app-ifc-new-edit-project",
  templateUrl: "./ifc-new-edit-project.component.html",
  styleUrls: ["./ifc-new-edit-project.component.css"],
})
export class IfcNewEditProjectComponent implements OnInit {
  // TODO: remove this object
  public testing = {
    crud: "CRUD",
    isUni: true,
    isTot: false,
  };
  public src: Resource;
  public data: any;
  constructor(protected route: ActivatedRoute, protected api: ApiService) {}

  ngOnInit(): void {
    const routeParams = this.route.snapshot.paramMap;
    const projectId = routeParams.get("projectId");
    this.api
      .get(`resource/Project/${projectId}/New_47_edit_32_project`)
      .then((data) => {
        this.data = new Resource(data);
      });
  }
}
