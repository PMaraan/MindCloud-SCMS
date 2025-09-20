export default function ensureDocNotEmpty(json){
  if (!json.content || json.content.length === 0){
    json.content = [{ type: 'paragraph' }];
  }
  return json;
}
