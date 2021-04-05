import { NgModule } from "@angular/core";
import { CommonModule } from "@angular/common";
import { LeafAlphanumericComponent } from "./leaf-alphanumeric/leaf-alphanumeric.component";
import { FormsModule } from "@angular/forms";
import { BoxFormComponent } from "./box-form/box-form.component";
import { BoxFormItemComponent } from "./box-form-item/box-form-item.component";
import { BoxFormRowComponent } from "./box-form-row/box-form-row.component";

@NgModule({
  declarations: [
    LeafAlphanumericComponent,
    BoxFormComponent,
    BoxFormItemComponent,
    BoxFormRowComponent,
  ],
  imports: [CommonModule, FormsModule],
})
export class TemplatesModule {}
