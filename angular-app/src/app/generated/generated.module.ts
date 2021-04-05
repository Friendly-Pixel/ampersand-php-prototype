import { NgModule } from "@angular/core";
import { CommonModule } from "@angular/common";
import { IfcNewEditProjectComponent } from "./ifc-new-edit-project/ifc-new-edit-project.component";
import { TemplatesModule } from "../templates/templates.module";

@NgModule({
  declarations: [IfcNewEditProjectComponent],
  imports: [CommonModule, TemplatesModule],
  exports: [IfcNewEditProjectComponent],
})
export class GeneratedModule {}
